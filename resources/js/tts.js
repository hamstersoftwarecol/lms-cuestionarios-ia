/**
 * Reproductor de texto a voz. Usa Gemini TTS (8 voces) y, si la IA no está
 * configurada o falla, recurre a la síntesis de voz del navegador.
 *
 * <div x-data="ttsPlayer({ url, voice, source: '#note-content' })">
 */
export function ttsPlayer({ url, voice = 'Kore', text = '', source = null, lang = 'es-ES' }) {
    return {
        voice,
        loading: false,
        playing: false,
        error: null,
        usingBrowser: false,
        audio: null,
        cache: {},

        text() {
            if (text) return text;
            const el = source ? document.querySelector(source) : null;
            return el ? (el.innerText || el.textContent).trim() : '';
        },

        async toggle() {
            if (this.playing) return this.stop();
            return this.play();
        },

        async play() {
            const content = this.text();
            this.error = null;

            if (!content) {
                this.error = 'No hay texto para leer.';
                return;
            }

            const key = `${this.voice}|${content}`;

            if (this.cache[key]) {
                return this.playUrl(this.cache[key]);
            }

            this.loading = true;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'audio/wav, application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ text: content, voice: this.voice }),
                });

                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    if (data.fallback) {
                        this.error = data.message || null;
                        return this.speakWithBrowser(content);
                    }
                    throw new Error(data.message || 'No se pudo generar el audio.');
                }

                const blobUrl = URL.createObjectURL(await response.blob());
                this.cache[key] = blobUrl;
                this.playUrl(blobUrl);
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        playUrl(blobUrl) {
            this.stop();
            this.usingBrowser = false;
            this.audio = new Audio(blobUrl);
            this.audio.addEventListener('ended', () => (this.playing = false));
            this.audio.play();
            this.playing = true;
        },

        speakWithBrowser(content) {
            if (!('speechSynthesis' in window)) {
                this.error = 'Tu navegador no admite texto a voz.';
                return;
            }

            this.stop();
            const utterance = new SpeechSynthesisUtterance(content.slice(0, 5000));
            utterance.lang = lang;
            utterance.onend = () => (this.playing = false);
            this.usingBrowser = true;
            this.playing = true;
            window.speechSynthesis.speak(utterance);
        },

        stop() {
            if (this.audio) {
                this.audio.pause();
                this.audio.currentTime = 0;
            }
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
            }
            this.playing = false;
        },
    };
}
