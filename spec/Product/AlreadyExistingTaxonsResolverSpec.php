<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\Product;

use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\Product\AlreadyExistingTaxonsResolver;
use Webgriffe\SyliusAkeneoPlugin\Product\TaxonsResolverInterface;

class AlreadyExistingTaxonsResolverSpec extends ObjectBehavior
{
    function let(TaxonRepositoryInterface $taxonRepository)
    {
        $this->beConstructedWith($taxonRepository);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(AlreadyExistingTaxonsResolver::class);
    }

    function it_implements_taxons_resolver_interface()
    {
        $this->shouldHaveType(TaxonsResolverInterface::class);
    }

    function it_returns_no_taxons_if_categories_array_does_not_exists()
    {
        $this->resolve([])->shouldReturn([]);
    }

    function it_returns_no_taxons_if_categories_array_is_empty()
    {
        $this->resolve(['categories' => []])->shouldReturn([]);
    }

    function it_returns_already_existing_taxons_which_have_same_code_of_those_in_categories_array(
        TaxonInterface $taxon1,
        TaxonInterface $taxon3,
        TaxonRepositoryInterface $taxonRepository
    ) {
        $taxonRepository->findOneBy(['code' => 'cat_1'])->willReturn($taxon1);
        $taxonRepository->findOneBy(['code' => 'cat_2'])->willReturn(null);
        $taxonRepository->findOneBy(['code' => 'cat_3'])->willReturn($taxon3);

        $this->resolve(['categories' => ['cat_1', 'cat_2', 'cat_3']])->shouldReturn([$taxon1, $taxon3]);
    }
}
