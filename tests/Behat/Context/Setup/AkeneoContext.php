<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use DateTime;
use Tests\Webgriffe\SyliusAkeneoPlugin\Akeneo\TestDouble\ApiClientMock;

final class AkeneoContext implements Context
{
    public function __construct(
        private ApiClientMock $apiClient,
    ) {
    }

    /**
     * @Given there is a product :identifier on Akeneo
     * @Given there is a product :identifier updated at :date on Akeneo
     */
    public function thereIsAProductUpdatedAtOnAkeneo(string $identifier, DateTime $date = null): void
    {
        $this->apiClient->addProductUpdatedAt($identifier, $date ?? new DateTime());
    }
}
