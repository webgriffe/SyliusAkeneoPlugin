<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Cli;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;

final class SpoolCommandContext implements Context
{

    /**
     * @When /^I import products from queue$/
     */
    public function iImportProductsFromQueue()
    {
        throw new PendingException();
    }
}
