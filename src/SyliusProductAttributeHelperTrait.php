<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

if (!interface_exists(\Sylius\Resource\Doctrine\Persistence\RepositoryInterface::class)) {
    class_alias(\Sylius\Resource\Doctrine\Persistence\RepositoryInterface::class, \Sylius\Component\Resource\Repository\RepositoryInterface::class);
}
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @psalm-type AkeneoAttribute array{code: string, type: string, labels: array<string, ?string>}
 */
trait SyliusProductAttributeHelperTrait
{
    /**
     * @return RepositoryInterface<ProductAttributeInterface>
     */
    abstract private function getProductAttributeRepository(): RepositoryInterface;

    /**
     * Return the list of Akeneo attribute codes whose code is used as a code for a Sylius SELECT attribute
     *
     * @psalm-suppress TooManyTemplateParams
     *
     * @param ResourceCursorInterface<array-key, AkeneoAttribute> $akeneoAttributes
     *
     * @return string[]
     */
    private function filterBySyliusSelectAttributeCodes(ResourceCursorInterface $akeneoAttributes): array
    {
        $syliusSelectAttributes = $this->getProductAttributeRepository()->findBy(['type' => SelectAttributeType::TYPE]);

        return $this->filterBySyliusAttributes($syliusSelectAttributes, $akeneoAttributes);
    }

    /**
     * Return the list of Akeneo attribute codes whose code is used as a code for a Sylius attribute
     *
     * @psalm-suppress TooManyTemplateParams
     *
     * @param ResourceCursorInterface<array-key, AkeneoAttribute> $akeneoAttributes
     *
     * @return string[]
     */
    private function filterBySyliusAttributeCodes(ResourceCursorInterface $akeneoAttributes): array
    {
        $syliusAttributes = $this->getProductAttributeRepository()->findAll();

        return $this->filterBySyliusAttributes($syliusAttributes, $akeneoAttributes);
    }

    /**
     * @psalm-suppress TooManyTemplateParams
     *
     * @param ProductAttributeInterface[] $syliusAttributes
     * @param ResourceCursorInterface<array-key, AkeneoAttribute> $akeneoAttributes
     *
     * @return string[]
     */
    private function filterBySyliusAttributes(array $syliusAttributes, ResourceCursorInterface $akeneoAttributes): array
    {
        $syliusAttributes = array_filter(
            array_map(
                static fn (ProductAttributeInterface $attribute): ?string => $attribute->getCode(),
                $syliusAttributes,
            ),
        );
        $attributeCodes = [];
        /** @var AkeneoAttribute $akeneoAttribute */
        foreach ($akeneoAttributes as $akeneoAttribute) {
            if (!in_array($akeneoAttribute['code'], $syliusAttributes, true)) {
                continue;
            }
            $attributeCodes[] = $akeneoAttribute['code'];
        }

        return $attributeCodes;
    }
}
