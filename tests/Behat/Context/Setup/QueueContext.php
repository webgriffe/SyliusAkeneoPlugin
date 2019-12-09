<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;

final class QueueContext implements Context
{
    /**
     * @Given /^there is one product model to import with identifier "([^"]*)" in the Akeneo queue$/
     */
    public function thereIsOneProductModelToImportWithIdentifierInTheAkeneoQueue(string $identifier)
    {
        throw new PendingException();
    }
}
