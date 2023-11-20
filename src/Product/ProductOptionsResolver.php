<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use RuntimeException;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionTranslationInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ProductOptionHelperTrait;

/**
 * @psalm-type AkeneoAttribute array{code: string, type: string, labels: array<string, ?string>}
 * @psalm-type AkeneoFamilyVariant array{code: string, labels: array<string, ?string>, variant_attribute_sets: list<array{level: int, axes: list<string>, attributes: list<string>}>}
 */
final class ProductOptionsResolver implements ProductOptionsResolverInterface
{
    use ProductOptionHelperTrait;

    /**
     * @param FactoryInterface<ProductOptionInterface> $productOptionFactory
     * @param FactoryInterface<ProductOptionTranslationInterface> $productOptionTranslationFactory
     */
    public function __construct(
        private AkeneoPimClientInterface $apiClient,
        private ProductOptionRepositoryInterface $productOptionRepository,
        private FactoryInterface $productOptionFactory,
        private FactoryInterface $productOptionTranslationFactory,
    ) {
    }

    public function resolve(array $akeneoProduct): array
    {
        /** @var string|null $parentCode */
        $parentCode = $akeneoProduct['parent'] ?? null;
        if ($parentCode === null) {
            throw new RuntimeException(
                sprintf(
                    'Cannot resolve product options for Akeneo product "%s" because it does not belong to any ' .
                    'product model.',
                    $akeneoProduct['identifier'] ?? '?',
                ),
            );
        }

        try {
            $productResponse = $this->apiClient->getProductModelApi()->get($parentCode);
        } catch (HttpException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new RuntimeException(sprintf('Cannot find product model "%s" on Akeneo.', $parentCode));
            }

            throw $e;
        }
        /** @var string $familyCode */
        $familyCode = $productResponse['family'];
        /** @var string $familyVariantCode */
        $familyVariantCode = $productResponse['family_variant'];

        try {
            /** @var AkeneoFamilyVariant $familyVariantResponse */
            $familyVariantResponse = $this->apiClient->getFamilyVariantApi()->get($familyCode, $familyVariantCode);
        } catch (HttpException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new RuntimeException(
                    sprintf(
                        'Cannot find family variant "%s" within family "%s" on Akeneo.',
                        $familyVariantCode,
                        $familyCode,
                    ),
                );
            }

            throw $e;
        }
        $productOptions = [];
        foreach ($familyVariantResponse['variant_attribute_sets'][0]['axes'] as $position => $attributeCode) {
            /** @var ProductOptionInterface|null $productOption */
            $productOption = $this->productOptionRepository->findOneBy(['code' => $attributeCode]);
            if ($productOption !== null) {
                $productOptions[] = $productOption;

                continue;
            }
            $productOption = $this->productOptionFactory->createNew();
            $productOption->setCode($attributeCode);
            $productOption->setPosition($position);

            try {
                /** @var AkeneoAttribute $akeneoAttribute */
                $akeneoAttribute = $this->apiClient->getAttributeApi()->get($attributeCode);
            } catch (HttpException $e) {
                if ($e->getResponse()->getStatusCode() === 404) {
                    throw new RuntimeException(
                        sprintf(
                            'Cannot resolve product options for product "%s" because one of its variant attributes, ' .
                            '"%s", cannot be found on Akeneo.',
                            $akeneoProduct['identifier'],
                            $attributeCode,
                        ),
                    );
                }

                throw $e;
            }
            $this->importProductOptionTranslations($akeneoAttribute, $productOption);
            $this->productOptionRepository->add($productOption);
            $productOptions[] = $productOption;
        }

        return $productOptions;
    }

    private function getDefinedLocaleCodes(): array
    {
        throw new RuntimeException('This method should not be invoked in this context.');
    }

    /**
     * @return FactoryInterface<ProductOptionTranslationInterface>
     */
    private function getProductOptionTranslationFactory(): FactoryInterface
    {
        return $this->productOptionTranslationFactory;
    }

    /**
     * @return FactoryInterface<ProductOptionValueTranslationInterface>
     */
    private function getProductOptionValueTranslationFactory(): FactoryInterface
    {
        throw new RuntimeException('This method should not be invoked in this context.');
    }

    /**
     * @return FactoryInterface<ProductOptionValueInterface>
     */
    private function getProductOptionValueFactory(): FactoryInterface
    {
        throw new RuntimeException('This method should not be invoked in this context.');
    }
}
