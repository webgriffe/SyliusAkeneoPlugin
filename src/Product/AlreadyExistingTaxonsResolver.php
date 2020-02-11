<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Webmozart\Assert\Assert;

class AlreadyExistingTaxonsResolver implements TaxonsResolverInterface
{
    /** @var TaxonRepositoryInterface */
    private $taxonRepository;

    public function __construct(TaxonRepositoryInterface $taxonRepository)
    {
        $this->taxonRepository = $taxonRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(array $akeneoProduct): array
    {
        $categories = $akeneoProduct['categories'] ?? [];
        if (empty($categories)) {
            return [];
        }
        $taxons = [];
        foreach ($categories as $categoryCode) {
            $taxon = $this->taxonRepository->findOneBy(['code' => $categoryCode]);
            if (!$taxon) {
                continue;
            }
            Assert::isInstanceOf($taxon, TaxonInterface::class);
            $taxons[] = $taxon;
        }

        return $taxons;
    }
}
