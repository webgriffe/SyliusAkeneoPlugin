<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\System;

use Behat\Behat\Context\Context;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContainer;
use Webmozart\Assert\Assert;

final class FilesystemContext implements Context
{
    /** @phpstan-ignore-next-line */
    private ?vfsStreamContainer $vfsStream = null;

    public function __construct(private string $temporaryDirectory, private string $temporaryFilesPrefix)
    {
    }

    /**
     * @BeforeScenario
     */
    public function before(): void
    {
        $this->vfsStream = vfsStream::setup('root');
    }

    /**
     * @Then there should not be any temporary file in the temporary files directory
     */
    public function thereShouldNotBeAnyTemporaryFileInTheTemporaryFilesDirectory(): void
    {
        Assert::isEmpty(glob(rtrim($this->temporaryDirectory, '/') . '/' . $this->temporaryFilesPrefix . '*'));
    }
}
