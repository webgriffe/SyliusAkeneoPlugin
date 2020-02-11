<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Core\Model\TaxonInterface;

interface TaxonsResolverInterface
{
    /**
     * @param array $akeneoProduct The Akeneo GET Product API response array (https://api.akeneo.com/api-reference.html#get_products__code_)
     *
     * @return TaxonInterface[]
     */
    public function resolve(array $akeneoProduct): array;
}
