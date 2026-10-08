<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;
use ZipArchive;

/**
 * Extrae el texto de PDFs, imágenes (incluidas notas manuscritas), Word y texto plano.
 *
 * Los PDF con capa de texto se leen localmente; los escaneados y las imágenes se
 * envían a Gemini, que actúa como OCR multimodal.
 */
class DocumentScanner
{
    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp', 'heic', 'heif'];

    private const OCR_PROMPT = <<<'PROMPT'
        Eres un sistema OCR experto. Extrae TODO el texto legible de este documento, incluidas las notas
        manuscritas, tablas (en formato Markdown), listas y fórmulas. Conserva la estructura usando títulos
        Markdown cuando sea evidente. No añadas comentarios, traducciones ni resúmenes: devuelve solo el texto
        extraído. Si no hay texto legible responde exactamente: [SIN_TEXTO]
        PROMPT;

    public function __construct(private readonly GeminiClient $gemini) {}

    public function scan(string $path, string $extension, ?string $mimeType = null): ScanResult
    {
        $extension = Str::lower($extension);

        $result = match (true) {
            $extension === 'pdf' => $this->scanPdf($path),
            in_array($extension, self::IMAGE_EXTENSIONS, true) => $this->scanWithAi($path, $mimeType ?? $this->mimeFor($extension), 'image'),
            $extension === 'docx' => new ScanResult($this->readDocx($path), 'docx', false),
            default => new ScanResult($this->readText($path), 'text', false),
        };

        $text = $this->normalize($result->text);

        if ($text === '' || $text === '[SIN_TEXTO]') {
            throw new GeminiException('No se encontró texto legible en el documento.');
        }

        return new ScanResult($text, $result->sourceType, $result->usedAi);
    }

    private function scanPdf(string $path): ScanResult
    {
        try {
            $pdf = (new PdfParser)->parseFile($path);
            $text = $this->normalize($pdf->getText());
            $pages = max(1, count($pdf->getPages()));

            // Menos de ~80 caracteres por página suele indicar un PDF escaneado sin capa de texto.
            if (mb_strlen($text) >= 80 * $pages || (! $this->gemini->isConfigured() && $text !== '')) {
                return new ScanResult($text, 'pdf', false);
            }
        } catch (Throwable $e) {
            Log::info('PDF sin capa de texto legible, se usará OCR con IA', ['error' => $e->getMessage()]);
        }

        return $this->scanWithAi($path, 'application/pdf', 'pdf');
    }

    private function scanWithAi(string $path, string $mimeType, string $sourceType): ScanResult
    {
        $text = $this->gemini->generateText([
            ['text' => self::OCR_PROMPT],
            ['inline_data' => ['mime_type' => $mimeType, 'data' => base64_encode((string) file_get_contents($path))]],
        ], 'extract', ['temperature' => 0]);

        return new ScanResult($text, $sourceType, true);
    }

    private function readDocx(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new GeminiException('No se pudo abrir el archivo de Word.');
        }

        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        $xml = str_replace(['</w:p>', '<w:br/>', '<w:tab/>'], ["\n\n", "\n", "\t"], $xml);

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function readText(string $path): string
    {
        $text = (string) file_get_contents($path);
        $encoding = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true) ?: 'UTF-8';

        return $encoding === 'UTF-8' ? $text : mb_convert_encoding($text, 'UTF-8', $encoding);
    }

    private function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function mimeFor(string $extension): string
    {
        return match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
            default => 'image/jpeg',
        };
    }
}
