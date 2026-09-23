<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Unit;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManager;
use Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManagerInterface;

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
        $temporaryFilePath1 = $this->temporaryFileManager->generateTemporaryFilePath('VARIANT_1');
        $temporaryFilePath2 = $this->temporaryFileManager->generateTemporaryFilePath('VARIANT_1');
        $temporaryFilePath3 = $this->temporaryFileManager->generateTemporaryFilePath('VARIANT_1');

        $this->temporaryFileManager->deleteAllTemporaryFiles('VARIANT_1');

        $this->assertFileDoesNotExist($temporaryFilePath1);
        $this->assertFileDoesNotExist($temporaryFilePath2);
        $this->assertFileDoesNotExist($temporaryFilePath3);
    }

    /** @test */
    public function it_does_not_delete_not_managed_temporary_files(): void
    {
        touch(vfsStream::url('root') . '/not-managed-temp_file');
        touch(vfsStream::url('root') . '/VARIANT_1-not_managed_temp_file');
        $managedTemporaryFilePath = $this->temporaryFileManager->generateTemporaryFilePath('VARIANT_1');

        $this->temporaryFileManager->deleteAllTemporaryFiles('VARIANT_1');

        $this->assertFileExists(vfsStream::url('root') . '/not-managed-temp_file');
        $this->assertFileExists(vfsStream::url('root') . '/VARIANT_1-not_managed_temp_file');
        $this->assertFileDoesNotExist($managedTemporaryFilePath);
    }

    /** @test */
    public function it_does_not_delete_not_managed_temporary_files_with_same_product_code_prefix(): void
    {
        $temporaryFilePath = $this->temporaryFileManager->generateTemporaryFilePath('CSV1');
        $otherProductTemporaryFilePath = $this->temporaryFileManager->generateTemporaryFilePath('CSV1-A3');

        $this->temporaryFileManager->deleteAllTemporaryFiles('CSV1');

        $this->assertFileExists($otherProductTemporaryFilePath);
        $this->assertFileDoesNotExist($temporaryFilePath);
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

    /** @test */
    public function it_keeps_the_prefix_for_identifiers_with_slashes(): void
    {
        $temporaryFileManager = $this->createTemporaryFilesManagerOnLocalFilesystem();

        $temporaryFilePath = $temporaryFileManager->generateTemporaryFilePath('ABT_APE-117/0433');

        $this->assertSame($this->localTemporaryDirectory, dirname($temporaryFilePath));
        $this->assertStringStartsWith('akeneo-', basename($temporaryFilePath));
    }

    /** @test */
    public function it_deletes_temporary_files_generated_for_identifiers_with_slashes(): void
    {
        $temporaryFileManager = $this->createTemporaryFilesManagerOnLocalFilesystem();
        $temporaryFilePath = $temporaryFileManager->generateTemporaryFilePath('ABT_APE-117/0433');

        $temporaryFileManager->deleteAllTemporaryFiles('ABT_APE-117/0433');

        $this->assertFileDoesNotExist($temporaryFilePath);
    }

    /** @test */
    public function it_does_not_delete_temporary_files_of_identifiers_differing_only_by_special_chars(): void
    {
        $temporaryFileManager = $this->createTemporaryFilesManagerOnLocalFilesystem();
        $slashTemporaryFilePath = $temporaryFileManager->generateTemporaryFilePath('ABT/0433');
        $dashTemporaryFilePath = $temporaryFileManager->generateTemporaryFilePath('ABT-0433');

        $temporaryFileManager->deleteAllTemporaryFiles('ABT-0433');

        $this->assertFileExists($slashTemporaryFilePath);
        $this->assertFileDoesNotExist($dashTemporaryFilePath);
    }

    /** @test */
    public function it_deletes_temporary_files_generated_for_long_identifiers(): void
    {
        $temporaryFileManager = $this->createTemporaryFilesManagerOnLocalFilesystem();
        $identifier = TemporaryFilesManagerInterface::PRODUCT_VARIANT_PREFIX . str_repeat('X', 50);
        $temporaryFilePath = $temporaryFileManager->generateTemporaryFilePath($identifier);

        $temporaryFileManager->deleteAllTemporaryFiles($identifier);

        $this->assertFileDoesNotExist($temporaryFilePath);
    }

    /** @test */
    public function it_does_not_delete_temporary_files_of_long_identifiers_sharing_the_same_beginning(): void
    {
        $temporaryFileManager = $this->createTemporaryFilesManagerOnLocalFilesystem();
        $identifier = TemporaryFilesManagerInterface::PRODUCT_VARIANT_PREFIX . str_repeat('X', 50);
        $temporaryFilePath = $temporaryFileManager->generateTemporaryFilePath($identifier . '_1');
        $otherTemporaryFilePath = $temporaryFileManager->generateTemporaryFilePath($identifier . '_2');

        $temporaryFileManager->deleteAllTemporaryFiles($identifier . '_1');

        $this->assertFileDoesNotExist($temporaryFilePath);
        $this->assertFileExists($otherTemporaryFilePath);
    }

    /**
     * Native tempnam() is only used on the local filesystem, while vfsStream goes through a different code path.
     */
    private function createTemporaryFilesManagerOnLocalFilesystem(): TemporaryFilesManager
    {
        $filesystem = new Filesystem();
        $localTemporaryDirectory = sys_get_temp_dir() . '/' . uniqid('akeneo-plugin-test-', true);
        $filesystem->mkdir($localTemporaryDirectory);
        // tempnam() returns resolved paths (e.g. /var is a symlink on macOS)
        $this->localTemporaryDirectory = (string) realpath($localTemporaryDirectory);

        return new TemporaryFilesManager(
            $filesystem,
            new Finder(),
            $this->localTemporaryDirectory,
            'akeneo-',
        );
    }
}
