<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Db;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;

final class ProductContext implements Context
{
    /**
     * @Then /^the product "([^"]*)" should exists with the right data$/
     */
    public function theProductShouldExistsWithTheRightData(string $code)
    {
        throw new PendingException();
    }
}
