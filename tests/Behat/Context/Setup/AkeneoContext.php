<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Behat\Behat\Context\Context;
use DateTime;
use Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\OfficialApiClientMock;
use Webmozart\Assert\Assert;

final class AkeneoContext implements Context
{
    /**
     * @param AkeneoPimClientInterface|OfficialApiClientMock $apiClient
     */
    public function __construct(
        private $apiClient
    ) {
    }

    /**
     * @Given there is a product :identifier updated at :date on Akeneo
     */
    public function thereIsAProductUpdatedAtOnAkeneo(string $identifier, DateTime $date): void
    {
        Assert::isInstanceOf($this->apiClient, OfficialApiClientMock::class);
        $this->apiClient->addProductUpdatedAt($identifier, $date);
    }

    /**
     * @Given /^there are (\d+) products on Akeneo$/
     * @Given /^there is (\d+) product on Akeneo$/
     */
    public function thereAreProductsOnAkeneo(int $count): void
    {
        Assert::isInstanceOf($this->apiClient, OfficialApiClientMock::class);
        for ($i = 1; $i <= $count; ++$i) {
            $this->apiClient->addProductUpdatedAt('product-' . $i, new DateTime());
        }
    }
}
