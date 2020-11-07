<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\ApiClientMock;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webmozart\Assert\Assert;

final class AkeneoContext implements Context
{
    /** @var ApiClientInterface|ApiClientMock */
    private $apiClient;

    public function __construct(ApiClientInterface $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * @Given there is a product :identifier updated at :date on Akeneo
     */
    public function thereIsAProductUpdatedAtOnAkeneo(string $identifier, \DateTime $date)
    {
        Assert::isInstanceOf($this->apiClient, ApiClientMock::class);
        $this->apiClient->addProductUpdatedAt($identifier, $date);
    }

    /**
     * @Given /^there are (\d+) products on Akeneo$/
     */
    public function thereAreProductsOnAkeneo(int $count)
    {
        for ($i = 1; $i <= $count; ++$i) {
            $this->apiClient->addProductUpdatedAt('product-' . $i, new \DateTime());
        }
    }
}
