<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

interface TemporaryFilesManagerInterface
{
    public const PRODUCT_VARIANT_PREFIX = 'product-variant-';

    public function generateTemporaryFilePath(string $fileIdentifier): string;

    public function deleteAllTemporaryFiles(string $fileIdentifier): void;
}
