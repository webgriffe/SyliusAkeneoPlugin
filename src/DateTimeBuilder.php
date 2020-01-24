<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

class DateTimeBuilder implements DateTimeBuilderInterface
{
    /**
     * @param string $time
     * @param \DateTimeZone|null $timezone
     * @return \DateTime
     * @throws \Exception
     */
    public function build($time = 'now', \DateTimeZone $timezone = null): \DateTime
    {
        return new \DateTime($time, $timezone);
    }
}
