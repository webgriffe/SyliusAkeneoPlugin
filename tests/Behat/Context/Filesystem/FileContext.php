<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Filesystem;

use Behat\Behat\Context\Context;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContainer;
use Webmozart\Assert\Assert;

final class FileContext implements Context
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
     * @Given /^there is a file with name "([^"]+)" that contains a datetime$/
     */
    public function thereIsAFileWithNameThatContainsADatetime($filename)
    {
        $file = vfsStream::url('root/' . $filename);
        $fileContent = file_get_contents($file);
        try {
            new \DateTime($fileContent);
        } catch (\Throwable $t) {
            throw new \RuntimeException(
                sprintf('File "%s" content is not a valid datetime: %s', $filename, $fileContent)
            );
        }
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
