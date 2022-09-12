<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\TestDouble;

use Webgriffe\SyliusAkeneoPlugin\DateTimeBuilderInterface;

class DateTimeBuilder implements DateTimeBuilderInterface
{
    public static $forcedNow;

    /**
     * @param string $time
     *
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
