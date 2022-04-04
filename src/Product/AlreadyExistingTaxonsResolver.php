<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;

final class AlreadyExistingTaxonsResolver implements TaxonsResolverInterface
{
    public function __construct(private TaxonRepositoryInterface $taxonRepository)
    {
    }

    /**
     * @inheritdoc
     */
    public function resolve(array $akeneoProduct): array
    {
        $categories = $akeneoProduct['categories'] ?? [];
        if ((is_countable($categories) ? count($categories) : 0) === 0) {
            return [];
        }
        $taxons = [];
        foreach ($categories as $categoryCode) {
            /** @var TaxonInterface|null $taxon */
            $taxon = $this->taxonRepository->findOneBy(['code' => $categoryCode]);
            if ($taxon === null) {
                continue;
            }
            $taxons[] = $taxon;
        }

        return $taxons;
    }
}
