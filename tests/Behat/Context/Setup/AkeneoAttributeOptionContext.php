<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryAttributeOptionApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\AttributeOption;

final class AkeneoAttributeOptionContext implements Context
{
    /**
     * @BeforeScenario
     */
    public function clear(): void
    {
        InMemoryAttributeOptionApi::$attributeOptions = [];
    }

    /**
     * @Given there is an option :code for attribute :attributeCode on Akeneo
     */
    public function thereIsAnOptionForAttributeOnAkeneo(string $code, string $attributeCode): void
    {
        InMemoryAttributeOptionApi::addResource(new AttributeOption($code, $attributeCode, 1, []));
    }
}
