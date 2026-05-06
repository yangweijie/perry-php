<?php

declare(strict_types=1);

namespace Perry\Build;

final readonly class CompileResult
{
    public function __construct(
        public bool $success,
        public string $outputFile,
        public string $sourceFile,
        public string $error = '',
    ) {}

    public static function success(string $outputFile, string $sourceFile): self
    {
        return new self(true, $outputFile, $sourceFile);
    }

    public static function failure(string $error, string $sourceFile = ''): self
    {
        return new self(false, '', $sourceFile, $error);
    }
}
