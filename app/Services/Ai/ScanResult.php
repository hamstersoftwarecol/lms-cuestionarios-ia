<?php

namespace App\Services\Ai;

final readonly class ScanResult
{
    public function __construct(
        public string $text,
        public string $sourceType,
        public bool $usedAi,
    ) {}
}
