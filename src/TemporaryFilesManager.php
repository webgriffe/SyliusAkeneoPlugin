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

    #[\Override]
    public function generateTemporaryFilePath(string $fileIdentifier): string
    {
        return $this->filesystem->tempnam(
            $this->temporaryDirectory,
            $this->getFilePrefix($fileIdentifier),
        );
    }

    #[\Override]
    public function deleteAllTemporaryFiles(string $fileIdentifier): void
    {
        if (!$this->filesystem->exists($this->temporaryDirectory)) {
            return;
        }
        $tempFiles = $this->finder->in($this->temporaryDirectory)->depth('== 0')->files()->name(
            '/^' . str_replace('*', '\*', $this->getFilePrefix($fileIdentifier)) . '[\w]+$/',
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
