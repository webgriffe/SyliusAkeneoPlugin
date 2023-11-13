<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryProductModelApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\ProductModel;

final class AkeneoProductModelContext implements Context
{
    /**
     * @BeforeScenario
     */
    public function clear(): void
    {
        InMemoryProductModelApi::$productModels = [];
    }

    /**
     * @Given there is a product model :code on Akeneo of family :familyCode having variant :familyVariantCode
     */
    public function thereIsAProductModelOnAkeneo(string $code, string $familyCode, string $familyVariantCode): void
    {
        InMemoryProductModelApi::addResource(ProductModel::create($code, [
            'family' => $familyCode,
            'family_variant' => $familyVariantCode,
        ]));
    }
}
