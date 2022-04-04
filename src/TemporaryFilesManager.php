<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class TemporaryFilesManager implements TemporaryFilesManagerInterface
{
    public function __construct(private Filesystem $filesystem, private Finder $finder, private string $temporaryDirectory, private string $temporaryFilesPrefix)
    {
    }

    public function generateTemporaryFilePath(): string
    {
        return $this->filesystem->tempnam($this->temporaryDirectory, $this->temporaryFilesPrefix);
    }

    public function deleteAllTemporaryFiles(): void
    {
        $tempFiles = $this->finder->in($this->temporaryDirectory)->depth('== 0')->files()->name(
            $this->temporaryFilesPrefix . '*'
        );
        foreach ($tempFiles as $tempFile) {
            $this->filesystem->remove($tempFile->getPathname());
        }
    }
}
