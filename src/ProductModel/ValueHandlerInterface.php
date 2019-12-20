<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;

use Sylius\Component\Core\Model\ProductInterface;

interface ValueHandlerInterface
{
    public function supports(ProductInterface $product, string $attribute, array $value): bool;

    public function handle(ProductInterface $product, string $attribute, array $value);
}
