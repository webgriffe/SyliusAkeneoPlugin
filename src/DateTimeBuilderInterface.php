<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

interface DateTimeBuilderInterface
{
    /**
     * @param string $time
     */
    public function build($time = 'now', \DateTimeZone $timezone = null): \DateTime;
}
