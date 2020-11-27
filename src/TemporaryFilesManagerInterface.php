<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

interface TemporaryFilesManagerInterface
{
    public function generateTemporaryFilePath(): string;

    public function deleteAllTemporaryFiles(): void;
}
