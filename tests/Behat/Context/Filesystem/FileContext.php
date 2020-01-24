<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Filesystem;

use Behat\Behat\Context\Context;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContainer;

final class FileContext implements Context
{
    /** @var vfsStreamContainer */
    private $vfsStream;

    /**
     * @Given /^there is a file with name "([^"]+)" and content "([^"]+)"$/
     */
    public function thereIsAFileWithNameAndContent($filename, $date)
    {
        if (!$this->vfsStream) {
            $this->vfsStream = vfsStream::setup('root');
        }
        vfsStream::newFile($filename)->at($this->vfsStream)->setContent($date);
    }
}
