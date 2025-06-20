<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class WebgriffeSyliusAkeneoPlugin extends Bundle
{
    use SyliusPluginTrait;

    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
