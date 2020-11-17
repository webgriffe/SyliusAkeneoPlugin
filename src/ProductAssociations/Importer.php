<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductAssociations;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Model\ProductAssociationInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Repository\ProductAssociationTypeRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Webmozart\Assert\Assert;

final class Importer implements ImporterInterface
{
    private const AKENEO_ENTITY = 'ProductAssociations';

    /** @var AkeneoPimClientInterface */
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
        AkeneoPimClientInterface $apiClient,
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
        try {
            $productVariantResponse = $this->apiClient->getProductApi()->get($identifier);
        } catch (HttpException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new \RuntimeException(sprintf('Cannot find product "%s" on Akeneo.', $identifier));
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
            throw new \RuntimeException(sprintf('Cannot find product "%s" on Sylius.', $productCode));
        }
        /** @var ProductInterface $product */
        $associations = $productVariantResponse['associations'];
        foreach ($associations as $associationTypeCode => $associationInfo) {
            $productAssociationType = $this->productAssociationTypeRepository->findOneBy(
                ['code' => $associationTypeCode]
            );

            $productsToAssociateIdentifiers = $associationInfo['products'] ?? [];
            $productModelsToAssociateIdentifiers = $associationInfo['product_models'] ?? [];
            $productAssociationIdentifiers = array_merge(
                $productsToAssociateIdentifiers,
                $productModelsToAssociateIdentifiers
            );
            if ($productAssociationType === null) {
                if (count($productAssociationIdentifiers) === 0) {
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
                            $productCode
                        )
                    );
                }
                $productsToAssociate[] = $productToAssociate;
            }

            $productAssociation = $this->productAssociationRepository->findOneBy(
                ['owner' => $product, 'type' => $productAssociationType]
            );
            if ($productAssociation === null) {
                $productAssociation = $this->productAssociationFactory->createNew();
                Assert::isInstanceOf($productAssociation, ProductAssociationInterface::class);
            }
            /** @var ProductAssociationInterface $productAssociation */
            $productAssociation->setOwner($product);
            $productAssociation->setType($productAssociationType);
            foreach ($productsToAssociate as $productToAssociate) {
                if (!$productAssociation->hasAssociatedProduct($productToAssociate)) {
                    $productAssociation->addAssociatedProduct($productToAssociate);
                }
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
        $searchBuilder = new SearchBuilder();
        $searchBuilder->addFilter('updated_at', '>', $sinceDate->format('Y-m-d H:i:s'));
        $products = $this->apiClient->getProductApi()->all(50, ['search' => $searchBuilder->getFilters()]);
        $identifiers = [];
        foreach ($products as $product) {
            $identifiers[] = $product['identifier'];
        }

        return $identifiers;
    }
}
