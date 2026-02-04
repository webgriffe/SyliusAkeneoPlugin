<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

final class DateTimeBuilder implements DateTimeBuilderInterface
{
    /**
     * @param string $time
     *
     * @throws \Exception
     */
    #[\Override]
    public function build($time = 'now', ?\DateTimeZone $timezone = null): \DateTime
    {
        return new \DateTime($time, $timezone);
    }
}
