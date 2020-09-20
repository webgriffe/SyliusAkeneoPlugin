<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\Product;

use PhpSpec\ObjectBehavior;
use Webgriffe\SyliusAkeneoPlugin\Product\StatusResolverInterface;
use Webgriffe\SyliusAkeneoPlugin\Product\VariantStatusResolver;

class VariantStatusResolverSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(VariantStatusResolver::class);
    }

    function it_implements_status_resolver_interface()
    {
        $this->shouldHaveType(StatusResolverInterface::class);
    }

    function it_resolve_to_enabled_status_when_akeneo_product_is_enabled()
    {
        $this->resolve(['enabled' => true])->shouldReturn(true);
    }

    function it_resolve_to_disabled_status_when_akeneo_product_is_disabled()
    {
        $this->resolve(['enabled' => false])->shouldReturn(false);
    }
}
