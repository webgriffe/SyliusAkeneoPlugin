<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use DateTime;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryProductApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Product;

final class AkeneoProductContext implements Context
{
    /**
     * @BeforeScenario
     */
    public function clear(): void
    {
        InMemoryProductApi::$products = [];
    }

    /**
     * @Given there is a product :identifier on Akeneo
     * @Given there is a product :identifier updated at :date on Akeneo
     */
    public function thereIsAProductUpdatedAtOnAkeneo(string $identifier, DateTime $date = null): void
    {
        InMemoryProductApi::addResource(Product::create($identifier, ['updated' => $date]));
    }

    /**
     * @Given /^the product "([^"]*)" has an association with product "([^"]*)" for association with code "([^"]*)"$/
     */
    public function theProductHasAnAssociationWithProductForAssociationWithCode(
        string $ownerProductIdentifier,
        string $associatedProductIdentifier,
        string $associationCode,
    ): void {
        $product = InMemoryProductApi::$products[$ownerProductIdentifier];
        $product->associations[$associationCode]['products'][] = $associatedProductIdentifier;
    }

    /**
     * @Given /^the product "([^"]*)" has a price attribute with amount "([^"]*)" and currency "([^"]*)"$/
     */
    public function theProductHasHasAPriceAttributeWithAmountAndCurrency(
        string $productIdentifier,
        string $amount,
        string $currency,
    ): void {
        $product = InMemoryProductApi::$products[$productIdentifier];
        if (!array_key_exists('price', $product->values)) {
            $product->values['price'][0] = [
                'locale' => null,
                'scope' => null,
                'data' => [],
            ];
        }
        $product->values['price'][0]['data'][] = [
            'amount' => $amount,
            'currency' => $currency,
        ];
    }

    /**
     * @Given /^the product "([^"]*)" has parent "([^"]*)"$/
     */
    public function theProductHasParent(string $productIdentifier, string $productModelCode): void
    {
        $product = InMemoryProductApi::$products[$productIdentifier];
        $product->parent = $productModelCode;
    }

    /**
     * @Given the product :identifier has an attribute :attributeCode with data :data
     */
    public function theProductHasAnAttributeWithData(
        string $identifier,
        string $attributeCode,
        mixed $data,
    ): void {
        $product = InMemoryProductApi::$products[$identifier];
        $product->values[$attributeCode][] = [
            'locale' => null,
            'scope' => null,
            'data' => $data,
        ];
    }
}
