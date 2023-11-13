<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryFamilyVariantApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\FamilyVariant;
use Webmozart\Assert\Assert;

final class AkeneoFamilyVariantContext implements Context
{
    /**
     * @BeforeScenario
     */
    public function clear(): void
    {
        InMemoryFamilyVariantApi::$familyVariants = [];
    }

    /**
     * @Given there is a family variant :code on Akeneo for the family :familyCode
     */
    public function thereIsAFamilyVariantOnAkeneo(string $code, string $familyCode): void
    {
        InMemoryFamilyVariantApi::addResource($familyCode, new FamilyVariant($code));
    }

    /**
     * @Given the family variant :code of family :familyCode has the attribute :attributeCode as axes of first level
     */
    public function theFamilyVariantHasTheAttributeAsAxesOfFirstLevel(
        string $code,
        string $familyCode,
        string $attributeCode,
    ): void {
        $familyVariant = InMemoryFamilyVariantApi::$familyVariants[$familyCode][$code];
        Assert::isInstanceOf($familyVariant, FamilyVariant::class);
        $familyVariant->variantAttributeSets[] = [
            'level' => 1,
            'axes' => [$attributeCode],
            'attributes' => [$attributeCode],
        ];
    }
}
