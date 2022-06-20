<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\System;

use Behat\Behat\Context\Context;
use Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble\DateTimeBuilder;

final class DateTimeContext implements Context
{
    /**
     * @Given /^current date time is "([^"]+)"$/
     */
    public function currentDateTimeIs(string $datetime): void
    {
        DateTimeBuilder::$forcedNow = $datetime;
    }
}
