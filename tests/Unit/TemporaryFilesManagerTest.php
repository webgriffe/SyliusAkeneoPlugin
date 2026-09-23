<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Unit;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManager;

final class TemporaryFilesManagerTest extends TestCase
{
    private TemporaryFilesManager $temporaryFileManager;

    private ?string $localTemporaryDirectory = null;

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

    protected function tearDown(): void
    {
        if ($this->localTemporaryDirectory !== null) {
            (new Filesystem())->remove($this->localTemporaryDirectory);
        }
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

    /** @test */
    public function it_deletes_temporary_files_generated_for_identifiers_with_regex_special_chars(): void
    {
        $temporaryFileManager = $this->createTemporaryFilesManagerOnLocalFilesystem();
        $temporaryFilePath = $temporaryFileManager->generateTemporaryFilePath('VARIANT+1');

        $temporaryFileManager->deleteAllTemporaryFiles('VARIANT+1');

        $this->assertFileDoesNotExist($temporaryFilePath);
    }

    /** @test */
    public function it_does_not_raise_warnings_when_deleting_temporary_files_for_identifiers_with_slashes(): void
    {
        $temporaryFileManager = $this->createTemporaryFilesManagerOnLocalFilesystem();
        // The Finder only evaluates the name pattern against existing files
        $temporaryFileManager->generateTemporaryFilePath('VARIANT_1');
        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;

            return true;
        });

        try {
            $temporaryFileManager->deleteAllTemporaryFiles('ABT_APE-117/0433');
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
    }

    /**
     * Native tempnam() is only used on the local filesystem, while vfsStream goes through a different code path.
     */
    private function createTemporaryFilesManagerOnLocalFilesystem(): TemporaryFilesManager
    {
        $filesystem = new Filesystem();
        $this->localTemporaryDirectory = sys_get_temp_dir() . '/' . uniqid('akeneo-plugin-test-', true);
        $filesystem->mkdir($this->localTemporaryDirectory);

        return new TemporaryFilesManager(
            $filesystem,
            new Finder(),
            $this->localTemporaryDirectory,
            'akeneo-',
        );
    }
}
