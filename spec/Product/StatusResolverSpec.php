<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\Product;

use PhpSpec\ObjectBehavior;
use Webgriffe\SyliusAkeneoPlugin\Product\StatusResolver;
use Webgriffe\SyliusAkeneoPlugin\Product\StatusResolverInterface;

class StatusResolverSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(StatusResolver::class);
    }

    function it_implements_status_resolver_interface()
    {
        $this->shouldHaveType(StatusResolverInterface::class);
    }

    function it_resolve_to_enabled_status_when_akeneo_product_has_a_parent_product_model()
    {
        $this->resolve(['parent' => 'model-code'])->shouldReturn(true);
    }

    function it_resolve_to_enabled_status_when_akeneo_product_has_no_parent_product_model_and_it_is_enabled()
    {
        $this->resolve(['parent' => null, 'enabled' => true])->shouldReturn(true);
    }

    function it_resolve_to_disabled_status_when_akeneo_product_has_no_parent_product_model_and_it_is_disabled()
    {
        $this->resolve(['parent' => null, 'enabled' => false])->shouldReturn(false);
    }
}
