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
            '/^' . preg_quote($this->getFilePrefix($fileIdentifier), '/') . '[\w]+$/',
        );
        foreach ($tempFiles as $tempFile) {
            $this->filesystem->remove($tempFile->getPathname());
        }
    }

    /**
     * tempnam() drops everything up to the last directory separator of the prefix, so the identifier is sanitized.
     * The hash of the original identifier keeps prefixes of identifiers differing only by special chars distinct.
     */
    private function getFilePrefix(string $fileIdentifier): string
    {
        $fileIdentifier = rtrim($fileIdentifier, '-');

        return sprintf(
            '%s-%s-%s-',
            rtrim($this->temporaryFilesPrefix, '-'),
            (string) preg_replace('/[^A-Za-z0-9_]+/', '_', $fileIdentifier),
            substr(hash('xxh3', $fileIdentifier), 0, 8),
        );
    }
}
