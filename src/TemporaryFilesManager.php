<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class TemporaryFilesManager implements TemporaryFilesManagerInterface
{
    public function __construct(
        private Filesystem $filesystem,
        private Finder $finder,
        private string $temporaryDirectory,
        private string $temporaryFilesPrefix,
    ) {
    }

    public function generateTemporaryFilePath(string $fileIdentifier): string
    {
        return $this->filesystem->tempnam(
            $this->temporaryDirectory,
            $this->getFilePrefix($fileIdentifier),
        );
    }

    public function deleteAllTemporaryFiles(string $fileIdentifier): void
    {
        $tempFiles = $this->finder->in($this->temporaryDirectory)->depth('== 0')->files()->name(
            '/^' . str_replace('*', '\*', $this->getFilePrefix($fileIdentifier)) . '*/',
        );
        foreach ($tempFiles as $tempFile) {
            $this->filesystem->remove($tempFile->getPathname());
        }
    }

    private function getFilePrefix(string $fileIdentifier): string
    {
        return sprintf(
            '%s-%s-',
            rtrim($this->temporaryFilesPrefix, '-'),
            rtrim($fileIdentifier, '-'),
        );
    }
}
