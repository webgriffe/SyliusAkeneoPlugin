<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Attribute;

if (!interface_exists(\Sylius\Resource\Doctrine\Persistence\RepositoryInterface::class)) {
    class_alias(\Sylius\Resource\Doctrine\Persistence\RepositoryInterface::class, \Sylius\Component\Resource\Repository\RepositoryInterface::class);
}
if (!interface_exists(\Sylius\Resource\Factory\FactoryInterface::class)) {
    class_alias(\Sylius\Resource\Factory\FactoryInterface::class, \Sylius\Component\Resource\Factory\FactoryInterface::class);
}
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use DateTime;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Product\Model\ProductAttributeTranslationInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webgriffe\SyliusAkeneoPlugin\Event\IdentifiersModifiedSinceSearchBuilderBuiltEvent;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webgriffe\SyliusAkeneoPlugin\ProductOptionHelperTrait;
use Webgriffe\SyliusAkeneoPlugin\SyliusProductAttributeHelperTrait;

/**
 * @psalm-type AkeneoAttribute array{code: string, type: string, labels: array<string, ?string>}
 */
final class Importer implements ImporterInterface
{
    public const SIMPLESELECT_TYPE = 'pim_catalog_simpleselect';

    public const MULTISELECT_TYPE = 'pim_catalog_multiselect';

    public const BOOLEAN_TYPE = 'pim_catalog_boolean';

    public const METRIC_TYPE = 'pim_catalog_metric';

    public const AKENEO_ENTITY = 'Attribute';

    use ProductOptionHelperTrait, SyliusProductAttributeHelperTrait;

    /**
     * @param FactoryInterface<ProductOptionTranslationInterface> $productOptionTranslationFactory
     * @param RepositoryInterface<ProductAttributeInterface> $productAttributeRepository
     * @param FactoryInterface<ProductAttributeTranslationInterface> $productAttributeTranslationFactory
     */
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private AkeneoPimClientInterface $apiClient,
        private ProductOptionRepositoryInterface $productOptionRepository,
        private FactoryInterface $productOptionTranslationFactory,
        private RepositoryInterface $productAttributeRepository,
        private FactoryInterface $productAttributeTranslationFactory,
    ) {
    }

    public function getAkeneoEntity(): string
    {
        return self::AKENEO_ENTITY;
    }

    public function getIdentifiersModifiedSince(DateTime $sinceDate): array
    {
        $searchBuilder = new SearchBuilder();
        $this->eventDispatcher->dispatch(
            new IdentifiersModifiedSinceSearchBuilderBuiltEvent($this, $searchBuilder, $sinceDate),
        );
        /**
         * @psalm-suppress TooManyTemplateParams
         *
         * @var ResourceCursorInterface<array-key, AkeneoAttribute> $akeneoAttributes
         */
        $akeneoAttributes = $this->apiClient->getAttributeApi()->all(50, ['search' => $searchBuilder->getFilters()]);

        return array_merge(
            $this->filterBySyliusAttributeCodes($akeneoAttributes),
            $this->filterSyliusOptionCodes($akeneoAttributes),
        );
    }

    public function import(string $identifier): void
    {
        /** @var AkeneoAttribute $akeneoAttribute */
        $akeneoAttribute = $this->apiClient->getAttributeApi()->get($identifier);

        $syliusProductAttribute = $this->productAttributeRepository->findOneBy(['code' => $identifier]);
        if ($syliusProductAttribute instanceof ProductAttributeInterface) {
            $this->importAttributeData($akeneoAttribute, $syliusProductAttribute);
        }

        $syliusProductOption = $this->productOptionRepository->findOneBy(['code' => $identifier]);
        if ($syliusProductOption instanceof ProductOptionInterface) {
            $this->importOptionData($akeneoAttribute, $syliusProductOption);
        }
    }

    /**
     * @return FactoryInterface<ProductOptionTranslationInterface>
     */
    private function getProductOptionTranslationFactory(): FactoryInterface
    {
        return $this->productOptionTranslationFactory;
    }

    private function getProductOptionRepository(): ProductOptionRepositoryInterface
    {
        return $this->productOptionRepository;
    }

    /**
     * @return RepositoryInterface<ProductAttributeInterface>
     */
    private function getProductAttributeRepository(): RepositoryInterface
    {
        return $this->productAttributeRepository;
    }

    /**
     * @param AkeneoAttribute $akeneoAttribute
     */
    private function importAttributeData(array $akeneoAttribute, ProductAttributeInterface $syliusProductAttribute): void
    {
        $this->importProductAttributeTranslations($akeneoAttribute, $syliusProductAttribute);
        $this->productAttributeRepository->add($syliusProductAttribute);
    }

    /**
     * @param AkeneoAttribute $akeneoAttribute
     */
    private function importOptionData(array $akeneoAttribute, ProductOptionInterface $syliusProductOption): void
    {
        $this->importProductOptionTranslations($akeneoAttribute, $syliusProductOption);
        $this->productOptionRepository->add($syliusProductOption);
        // TODO: Update also the position of the option? The problem is that this position is on family variant entity!
    }

    /**
     * @param AkeneoAttribute $akeneoAttribute
     */
    private function importProductAttributeTranslations(array $akeneoAttribute, ProductAttributeInterface $syliusProductAttribute): void
    {
        foreach ($akeneoAttribute['labels'] as $locale => $label) {
            $productAttributeTranslation = $syliusProductAttribute->getTranslation($locale);
            if ($productAttributeTranslation->getLocale() === $locale) {
                $productAttributeTranslation->setName($label);

                continue;
            }
            $newProductAttributeTranslation = $this->productAttributeTranslationFactory->createNew();
            $newProductAttributeTranslation->setLocale($locale);
            $newProductAttributeTranslation->setName($label);
            $syliusProductAttribute->addTranslation($newProductAttributeTranslation);
        }
    }
}
