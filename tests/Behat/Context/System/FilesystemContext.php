<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\System;

use Behat\Behat\Context\Context;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContainer;
use Webmozart\Assert\Assert;

final class FilesystemContext implements Context
{
    /** @var vfsStreamContainer */
    private $vfsStream;

    /**
     * @BeforeScenario
     */
    public function before()
    {
        $this->vfsStream = vfsStream::setup('root');
    }

    /**
     * @Given /^there is a file with name "([^"]+)" and content "([^"]+)"$/
     */
    public function thereIsAFileWithNameAndContent($filename, $date)
    {
        vfsStream::newFile($filename)->at($this->vfsStream)->setContent($date);
    }

    /**
     * @Given /^there is a file with name "([^"]+)" that contains "([^"]+)"$/
     */
    public function thereIsAFileWithNameThatContains($filename, $content)
    {
        $file = vfsStream::url('root/' . $filename);
        $actualFileContent = file_get_contents($file);
        Assert::same($actualFileContent, $content);
    }

    /**
     * @Given /^there is no file with name "([^"]+)"$/
     */
    public function thereIsNoFileWithName($filename)
    {
        $file = vfsStream::url('root/' . $filename);
        Assert::false(file_exists($file));
    }
}
