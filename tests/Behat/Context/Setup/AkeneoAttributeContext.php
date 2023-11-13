<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryAttributeApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Attribute;

final class AkeneoAttributeContext implements Context
{
    /**
     * @BeforeScenario
     */
    public function clear(): void
    {
        InMemoryAttributeApi::$attributes = [];
    }

    /**
     * @Given there is an attribute :code on Akeneo of type :type
     */
    public function thereIsAnAttributeOnAkeneo(string $code, string $type): void
    {
        InMemoryAttributeApi::addResource(new Attribute($code, $type));
    }
}
