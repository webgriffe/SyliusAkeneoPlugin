<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductAssociations;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Model\ProductAssociationInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Repository\ProductAssociationTypeRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webmozart\Assert\Assert;

class Importer implements ImporterInterface
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
        if (!$productVariantResponse) {
            throw new \RuntimeException(sprintf('Cannot find product "%s" on Akeneo.', $identifier));
        }

        $product = $this->productRepository->findOneByCode($identifier);
        if ($product === null) {
            throw new \RuntimeException(sprintf('Cannot find product "%s" on Sylius.', $identifier));
        }
        /** @var ProductInterface $product */
        $associations = $productVariantResponse['associations'];
        foreach ($associations as $associationTypeCode => $associationInfo) {
            $productAssociationType = $this->productAssociationTypeRepository->findOneBy(
                ['code' => $associationTypeCode]
            );

            // todo: actually only association of "products" is handled
            $productAssociationIdentifiers = $associationInfo['products'] ?? [];
            if ($productAssociationType === null) {
                if (empty($productAssociationIdentifiers)) {
                    continue;
                }

                throw new \RuntimeException(
                    sprintf(
                        'There are products for the association type "%s" but it does not exists on Sylius.',
                        $associationTypeCode
                    )
                );
            }
            /** @var ProductAssociationTypeInterface $productAssociationType */
            $productsToAssociate = [];
            foreach ($productAssociationIdentifiers as $productToAssociateIdentifier) {
                $productToAssociate = $this->productRepository->findOneByCode($productToAssociateIdentifier);
                if ($productToAssociate === null) {
                    throw new \RuntimeException(
                        sprintf(
                            'Cannot associate the product "%s" to product "%s" because the former does not exists' .
                            ' on Sylius',
                            $productToAssociateIdentifier,
                            $identifier
                        )
                    );
                }
                $productsToAssociate[] = $productToAssociate;
            }

            // todo: check if product association already exists
            $productAssociation = $this->productAssociationFactory->createNew();
            Assert::isInstanceOf($productAssociation, ProductAssociationInterface::class);
            /** @var ProductAssociationInterface $productAssociation */
            $productAssociation->setOwner($product);
            $productAssociation->setType($productAssociationType);
            foreach ($productsToAssociate as $productToAssociate) {
                $productAssociation->addAssociatedProduct($productToAssociate);
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
}
