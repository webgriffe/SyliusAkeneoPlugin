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
    private TemporaryFilesManager $temporaryFileManager;

    protected function setUp(): void
    {
        vfsStream::setup();
        $this->temporaryFileManager = new TemporaryFilesManager(
            new Filesystem(),
            new Finder(),
            vfsStream::url('root'),
            'akeneo-',
        );
    }

    /** @test */
    public function it_generates_temporary_file_path(): void
    {
        $this->assertMatchesRegularExpression(
            '|' . vfsStream::url('root') . '/akeneo-.*|',
            $this->temporaryFileManager->generateTemporaryFilePath('VARIANT_1'),
        );
    }

    /** @test */
    public function it_deletes_all_temporary_files(): void
    {
        touch(vfsStream::url('root') . '/akeneo-VARIANT_1-temp1');
        touch(vfsStream::url('root') . '/akeneo-VARIANT_1-temp2');
        touch(vfsStream::url('root') . '/akeneo-VARIANT_1-temp3');

        $this->temporaryFileManager->deleteAllTemporaryFiles('VARIANT_1');

        $this->assertFileDoesNotExist(vfsStream::url('root') . '/akeneo-VARIANT_1-temp1');
        $this->assertFileDoesNotExist(vfsStream::url('root') . '/akeneo-VARIANT_1-temp2');
        $this->assertFileDoesNotExist(vfsStream::url('root') . '/akeneo-VARIANT_1-temp3');
    }

    /** @test */
    public function it_does_not_delete_not_managed_temporary_files(): void
    {
        touch(vfsStream::url('root') . '/not-managed-temp_file');
        touch(vfsStream::url('root') . '/VARIANT_1-not_managed_temp_file');
        touch(vfsStream::url('root') . '/akeneo-VARIANT_1-managed_temp_file');

        $this->temporaryFileManager->deleteAllTemporaryFiles('VARIANT_1');

        $this->assertFileExists(vfsStream::url('root') . '/not-managed-temp_file');
        $this->assertFileExists(vfsStream::url('root') . '/VARIANT_1-not_managed_temp_file');
        $this->assertFileDoesNotExist(vfsStream::url('root') . '/akeneo-VARIANT_1-managed_temp_file');
    }

    /** @test */
    public function it_does_not_delete_not_managed_temporary_files_with_same_product_code_prefix(): void
    {
        touch(vfsStream::url('root') . '/akeneo-CSV1-fCZfOu');
        touch(vfsStream::url('root') . '/akeneo-CSV1-A3-324234');

        $this->temporaryFileManager->deleteAllTemporaryFiles('CSV1');

        $this->assertFileExists(vfsStream::url('root') . '/akeneo-CSV1-A3-324234');
        $this->assertFileDoesNotExist(vfsStream::url('root') . '/akeneo-CSV1-fCZfOu');
    }
}
