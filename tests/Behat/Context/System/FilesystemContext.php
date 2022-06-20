<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\System;

use Behat\Behat\Context\Context;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContainer;
use Webmozart\Assert\Assert;

final class FilesystemContext implements Context
{
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
     * @Given /^there is a file with name "([^"]+)" and content "([^"]+)"$/
     */
    public function thereIsAFileWithNameAndContent(string $filename, string $date): void
    {
        vfsStream::newFile($filename)->at($this->vfsStream)->setContent($date);
    }

    /**
     * @Given /^there is a file with name "([^"]+)" that contains "([^"]+)"$/
     */
    public function thereIsAFileWithNameThatContains(string $filename, string $content): void
    {
        $file = vfsStream::url('root/' . $filename);
        $actualFileContent = file_get_contents($file);
        Assert::same($actualFileContent, $content);
    }

    /**
     * @Then /^there should not be any temporary file in the temporary files directory$/
     */
    public function thereShouldNotBeAnyTemporaryFileInTheTemporaryFilesDirectory(): void
    {
        Assert::isEmpty(glob(rtrim($this->temporaryDirectory, '/') . '/' . $this->temporaryFilesPrefix . '*'));
    }
}
