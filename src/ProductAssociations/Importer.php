<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductAssociations;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductInterface as BaseProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Model\ProductAssociationInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Repository\ProductAssociationTypeRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webmozart\Assert\Assert;

final class Importer implements ImporterInterface
{
    private const AKENEO_ENTITY = 'ProductAssociations';

    /** @var ApiClientInterface */
    private $apiClient;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var RepositoryInterface */
    private $productAssociationRepository;

    /** @var ProductAssociationTypeRepositoryInterface */
    private $productAssociationTypeRepository;

    /** @var FactoryInterface */
    private $productAssociationFactory;

    public function __construct(
        ApiClientInterface $apiClient,
        ProductRepositoryInterface $productRepository,
        RepositoryInterface $productAssociationRepository,
        ProductAssociationTypeRepositoryInterface $productAssociationTypeRepository,
        FactoryInterface $productAssociationFactory
    ) {
        $this->apiClient = $apiClient;
        $this->productRepository = $productRepository;
        $this->productAssociationRepository = $productAssociationRepository;
        $this->productAssociationTypeRepository = $productAssociationTypeRepository;
        $this->productAssociationFactory = $productAssociationFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getAkeneoEntity(): string
    {
        return self::AKENEO_ENTITY;
    }

    /**
     * {@inheritdoc}
     */
    public function import(string $identifier): void
    {
        $productVariantResponse = $this->apiClient->findProduct($identifier);
        if ($productVariantResponse === null) {
            throw new \RuntimeException(sprintf('Cannot find product "%s" on Akeneo.', $identifier));
        }

        $parentCode = $productVariantResponse['parent'];
        if ($parentCode !== null) {
            $productCode = $parentCode;
        } else {
            $productCode = $identifier;
        }

        $product = $this->productRepository->findOneByCode($productCode);
        if ($product === null) {
            throw new \RuntimeException(sprintf('Cannot find product "%s" on Sylius.', $productCode));
        }
        /** @var ProductInterface $product */
        $associations = $productVariantResponse['associations'];
        foreach ($associations as $associationTypeCode => $associationInfo) {
            /** @var ProductAssociationTypeInterface|null $productAssociationType */
            $productAssociationType = $this->productAssociationTypeRepository->findOneBy(
                ['code' => $associationTypeCode]
            );

            if ($productAssociationType === null) {
                continue;
            }

            $productsToAssociateIdentifiers = $associationInfo['products'] ?? [];
            $productModelsToAssociateIdentifiers = $associationInfo['product_models'] ?? [];
            $productAssociationIdentifiers = array_merge(
                $productsToAssociateIdentifiers,
                $productModelsToAssociateIdentifiers
            );
            /** @var ProductAssociationTypeInterface $productAssociationType */
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
                ['owner' => $product, 'type' => $productAssociationType]
            );
            if ($productAssociation === null) {
                /** @var ProductAssociationInterface|object $productAssociation */
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
     * {@inheritdoc}
     */
    public function getIdentifiersModifiedSince(\DateTime $sinceDate): array
    {
        $products = $this->apiClient->findProductsModifiedSince($sinceDate);
        $identifiers = [];
        foreach ($products as $product) {
            $identifiers[] = $product['identifier'];
        }

        return $identifiers;
    }

    /**
     * @param Collection<int|string, BaseProductInterface> $syliusAssociations
     * @param Collection<int|string, BaseProductInterface> $akeneoAssociations
     * @return Collection<int|string, BaseProductInterface>
     */
    private function getProductsToAdd(Collection $syliusAssociations, Collection $akeneoAssociations): Collection
    {
        return $akeneoAssociations->filter(
            static function (BaseProductInterface $productToAssociate) use ($syliusAssociations): bool {
                return !$syliusAssociations->contains($productToAssociate);
            }
        );
    }

    /**
     * @param Collection<int|string, BaseProductInterface> $syliusAssociations
     * @param Collection<int|string, BaseProductInterface> $akeneoAssociations
     * @return Collection<int|string, BaseProductInterface>
     */
    private function getProductsToRemove(Collection $syliusAssociations, Collection $akeneoAssociations): Collection
    {
        return $syliusAssociations->filter(
            static function (BaseProductInterface $productToAssociate) use ($akeneoAssociations): bool {
                return !$akeneoAssociations->contains($productToAssociate);
            }
        );
    }
}
