<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble;

use Webgriffe\SyliusAkeneoPlugin\DateTimeBuilderInterface;

class DateTimeBuilder implements DateTimeBuilderInterface
{
    public static $forcedNow = null;

    /**
     * @param string $time
     * @param \DateTimeZone|null $timezone
     * @return \DateTime
     * @throws \Exception
     */
    public function build($time = 'now', \DateTimeZone $timezone = null): \DateTime
    {
        if (self::$forcedNow) {
            $dateTime = new \DateTime(self::$forcedNow, $timezone);
            if (null !== $time) {
                $dateTime->modify($time);
            }
            return $dateTime;
        }
        return new \DateTime($time, $timezone);
    }
}
