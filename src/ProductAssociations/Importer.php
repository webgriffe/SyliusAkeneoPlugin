<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductAssociations;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use RuntimeException;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Model\ProductAssociationInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Model\ProductInterface as BaseProductInterface;
use Sylius\Component\Product\Repository\ProductAssociationTypeRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webmozart\Assert\Assert;

final class Importer implements ImporterInterface
{
    private const AKENEO_ENTITY = 'ProductAssociations';

    public function __construct(
        private AkeneoPimClientInterface $apiClient,
        private ProductRepositoryInterface $productRepository,
        private RepositoryInterface $productAssociationRepository,
        private ProductAssociationTypeRepositoryInterface $productAssociationTypeRepository,
        private FactoryInterface $productAssociationFactory,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getAkeneoEntity(): string
    {
        return self::AKENEO_ENTITY;
    }

    /**
     * @inheritdoc
     */
    public function import(string $identifier): void
    {
        try {
            $productVariantResponse = $this->apiClient->getProductApi()->get($identifier);
        } catch (HttpException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new RuntimeException(sprintf('Cannot find product "%s" on Akeneo.', $identifier));
            }

            throw $e;
        }

        $parentCode = $productVariantResponse['parent'];
        if ($parentCode !== null) {
            $productCode = $parentCode;
        } else {
            $productCode = $identifier;
        }

        $product = $this->productRepository->findOneByCode($productCode);
        if ($product === null) {
            throw new RuntimeException(sprintf('Cannot find product "%s" on Sylius.', $productCode));
        }
        $associations = $productVariantResponse['associations'];
        foreach ($associations as $associationTypeCode => $associationInfo) {
            /** @var ProductAssociationTypeInterface|null $productAssociationType */
            $productAssociationType = $this->productAssociationTypeRepository->findOneBy(
                ['code' => $associationTypeCode],
            );

            if ($productAssociationType === null) {
                continue;
            }

            $productsToAssociateIdentifiers = $associationInfo['products'] ?? [];
            $productModelsToAssociateIdentifiers = $associationInfo['product_models'] ?? [];
            $productAssociationIdentifiers = array_merge(
                $productsToAssociateIdentifiers,
                $productModelsToAssociateIdentifiers,
            );
            /** @var Collection<int|string, BaseProductInterface> $productsToAssociate */
            $productsToAssociate = new ArrayCollection();
            foreach ($productAssociationIdentifiers as $productToAssociateIdentifier) {
                $productToAssociate = $this->productRepository->findOneByCode($productToAssociateIdentifier);
                if ($productToAssociate === null) {
                    continue;
                }
                $productsToAssociate->add($productToAssociate);
            }

            /** @var ProductAssociationInterface|null $productAssociation */
            $productAssociation = $this->productAssociationRepository->findOneBy(
                ['owner' => $product, 'type' => $productAssociationType],
            );
            if ($productAssociation === null) {
                $productAssociation = $this->productAssociationFactory->createNew();
                Assert::isInstanceOf($productAssociation, ProductAssociationInterface::class);
                $productAssociation->setOwner($product);
                $productAssociation->setType($productAssociationType);
            }

            $productsToAdd = $this->getProductsToAdd($productAssociation->getAssociatedProducts(), $productsToAssociate);
            $productsToRemove = $this->getProductsToRemove($productAssociation->getAssociatedProducts(), $productsToAssociate);

            foreach ($productsToAdd as $productToAdd) {
                $productAssociation->addAssociatedProduct($productToAdd);
            }
            foreach ($productsToRemove as $productToRemove) {
                $productAssociation->removeAssociatedProduct($productToRemove);
            }

            $product->addAssociation($productAssociation);
            $this->productAssociationRepository->add($productAssociation);
        }
    }

    /**
     * @inheritdoc
     */
    public function getIdentifiersModifiedSince(DateTime $sinceDate): array
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder->addFilter('updated', '>', $sinceDate->format('Y-m-d H:i:s'));
        $products = $this->apiClient->getProductApi()->all(50, ['search' => $searchBuilder->getFilters()]);
        $identifiers = [];
        foreach ($products as $product) {
            Assert::isArray($product);
            Assert::keyExists($product, 'identifier');
            $productIdentifier = (string) $product['identifier'];
            Assert::stringNotEmpty($productIdentifier);
            $identifiers[] = $productIdentifier;
        }

        return $identifiers;
    }

    /**
     * @param Collection<int|string, BaseProductInterface> $syliusAssociations
     * @param Collection<int|string, BaseProductInterface> $akeneoAssociations
     *
     * @return Collection<int|string, BaseProductInterface>
     */
    private function getProductsToAdd(Collection $syliusAssociations, Collection $akeneoAssociations): Collection
    {
        return $akeneoAssociations->filter(
            static fn (BaseProductInterface $productToAssociate): bool => !$syliusAssociations->contains($productToAssociate),
        );
    }

    /**
     * @param Collection<int|string, BaseProductInterface> $syliusAssociations
     * @param Collection<int|string, BaseProductInterface> $akeneoAssociations
     *
     * @return Collection<int|string, BaseProductInterface>
     */
    private function getProductsToRemove(Collection $syliusAssociations, Collection $akeneoAssociations): Collection
    {
        return $syliusAssociations->filter(
            static fn (BaseProductInterface $productToAssociate): bool => !$akeneoAssociations->contains($productToAssociate),
        );
    }
}
