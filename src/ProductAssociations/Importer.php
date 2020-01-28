<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductAssociations;

use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;

class Importer implements ImporterInterface
{
    private const AKENEO_ENTITY = 'ProductAssociations';

    /** @var ApiClientInterface */
    private $apiClient;

    public function __construct(ApiClientInterface $apiClient)
    {
        $this->apiClient = $apiClient;
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
