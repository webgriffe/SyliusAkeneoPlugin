<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use RuntimeException;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class ProductOptionsResolver implements ProductOptionsResolverInterface
{
    public function __construct(
        private AkeneoPimClientInterface $apiClient,
        private ProductOptionRepositoryInterface $productOptionRepository,
        private FactoryInterface $productOptionFactory,
        private FactoryInterface $productOptionTranslationFactory,
    ) {
    }

    /**
     * @inheritdoc
     */
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
            Assert::isInstanceOf($productOption, ProductOptionInterface::class);
            $productOption->setCode($attributeCode);
            $productOption->setPosition($position);

            try {
                $attributeResponse = $this->apiClient->getAttributeApi()->get($attributeCode);
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
            foreach ($attributeResponse['labels'] as $locale => $label) {
                $productOptionTranslation = $productOption->getTranslation($locale);
                if ($productOptionTranslation->getLocale() === $locale) {
                    $productOptionTranslation->setName($label);

                    continue;
                }
                /** @var ProductOptionTranslationInterface $newProductOptionTranslation */
                $newProductOptionTranslation = $this->productOptionTranslationFactory->createNew();
                $newProductOptionTranslation->setLocale($locale);
                $newProductOptionTranslation->setName($label);
                $productOption->addTranslation($newProductOptionTranslation);
            }
            $this->productOptionRepository->add($productOption);
            $productOptions[] = $productOption;
        }

        return $productOptions;
    }
}
