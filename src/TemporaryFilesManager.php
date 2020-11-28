<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class TemporaryFilesManager implements TemporaryFilesManagerInterface
{
    /** @var Filesystem */
    private $filesystem;

    /** @var Finder */
    private $finder;

    /** @var string */
    private $temporaryDirectory;

    /** @var string */
    private $temporaryFilesPrefix;

    public function __construct(
        Filesystem $filesystem,
        Finder $finder,
        string $temporaryDirectory,
        string $temporaryFilesPrefix
    ) {
        $this->filesystem = $filesystem;
        $this->finder = $finder;
        $this->temporaryDirectory = $temporaryDirectory;
        $this->temporaryFilesPrefix = $temporaryFilesPrefix;
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
