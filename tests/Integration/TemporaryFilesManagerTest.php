<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManager;

final class TemporaryFilesManagerTest extends TestCase
{
    private $temporaryFileManager;

    protected function setUp(): void
    {
        vfsStream::setup();
        $this->temporaryFileManager = new TemporaryFilesManager(
            new Filesystem(),
            new Finder(),
            vfsStream::url('root'),
            'akeneo-'
        );
    }

    /**
     * @test
     */
    public function it_generates_temporary_file_path()
    {
        $this->assertRegExp(
            '|' . vfsStream::url('root') . '/akeneo-.*|',
            $this->temporaryFileManager->generateTemporaryFilePath()
        );
    }

    /**
     * @test
     */
    public function it_deletes_all_temporary_files()
    {
        touch(vfsStream::url('root') . '/akeneo-temp1');
        touch(vfsStream::url('root') . '/akeneo-temp2');
        touch(vfsStream::url('root') . '/akeneo-temp3');

        $this->temporaryFileManager->deleteAllTemporaryFiles();

        $this->assertFileNotExists(vfsStream::url('root') . '/akeneo-temp1');
        $this->assertFileNotExists(vfsStream::url('root') . '/akeneo-temp2');
        $this->assertFileNotExists(vfsStream::url('root') . '/akeneo-temp3');
    }

    /**
     * @test
     */
    public function it_does_not_delete_not_managed_temporary_files()
    {
        touch(vfsStream::url('root') . '/not-managed-temp-file');

        $this->temporaryFileManager->deleteAllTemporaryFiles();

        $this->assertFileExists(vfsStream::url('root') . '/not-managed-temp-file');
    }
}
