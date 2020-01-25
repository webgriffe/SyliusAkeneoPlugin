<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Cocur\Slugify\SlugifyInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImmutableSlugValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

class ImmutableSlugValueHandlerSpec extends ObjectBehavior
{
    private const AKENEO_ATTRIBUTE = 'akeneo_attribute_to_slugify';

    private const VALUE_TO_SLUGIFY = 'Value to slugify';

    private const SLUGIFIED_VALUE = 'value-to-slugify';

    function let(
        SlugifyInterface $slugify,
        FactoryInterface $productTranslationFactory,
        TranslationLocaleProviderInterface $translationLocaleProvider,
        RepositoryInterface $productTranslationRepository
    ) {
        $slugify->slugify(self::VALUE_TO_SLUGIFY)->willReturn(self::SLUGIFIED_VALUE);
        $translationLocaleProvider->getDefinedLocalesCodes()->willReturn(['en_US', 'it_IT']);
        $productTranslationRepository->findOneBy(['slug' => self::SLUGIFIED_VALUE, 'locale' => 'en_US'])->willReturn(null);
        $productTranslationRepository->findOneBy(['slug' => self::SLUGIFIED_VALUE, 'locale' => 'it_IT'])->willReturn(null);
        $this->beConstructedWith(
            $slugify,
            $productTranslationFactory,
            $translationLocaleProvider,
            $productTranslationRepository,
            self::AKENEO_ATTRIBUTE
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ImmutableSlugValueHandler::class);
    }

    function it_implements_value_handler_interface()
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    function it_supports_product_variant_as_subject(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, self::AKENEO_ATTRIBUTE, [])->shouldReturn(true);
    }

    function it_does_not_support_other_type_of_subject()
    {
        $this->supports(new \stdClass(), self::AKENEO_ATTRIBUTE, [])->shouldReturn(false);
    }

    function it_supports_provided_akeneo_attribute(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, self::AKENEO_ATTRIBUTE, [])->shouldReturn(true);
    }

    function it_does_not_support_any_other_akeneo_attribute(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, 'other_attribute', [])->shouldReturn(false);
    }

    function it_throws_exception_during_handle_when_subject_is_not_product_variant()
    {
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    sprintf(
                        'This immutable slug value handler only supports instances of %s, %s given.',
                        ProductVariantInterface::class,
                        \stdClass::class
                    )
                )
            )
            ->during('handle', [new \stdClass(), self::AKENEO_ATTRIBUTE, []]);
    }

    function it_sets_sluggified_value_on_product_translation_slug_for_the_given_locale(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductTranslationInterface $productTranslation
    ) {
        $productVariant->getProduct()->willReturn($product);
        $product->getTranslation('en_US')->willReturn($productTranslation);
        $productTranslation->getLocale()->willReturn('en_US');
        $productTranslation->getSlug()->willReturn(null);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE, [['locale' => 'en_US', 'scope' => null, 'data' => self::VALUE_TO_SLUGIFY]]);

        $productTranslation->setSlug(self::SLUGIFIED_VALUE)->shouldHaveBeenCalled();
    }

    function it_does_not_change_slug_if_already_set_on_product_translation(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductTranslationInterface $productTranslation
    ) {
        $productVariant->getProduct()->willReturn($product);
        $product->getTranslation('en_US')->willReturn($productTranslation);
        $productTranslation->getLocale()->willReturn('en_US');
        $productTranslation->getSlug()->willReturn('already-existent-slug');

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE, [['locale' => 'en_US', 'scope' => null, 'data' => self::VALUE_TO_SLUGIFY]]);

        $productTranslation->setSlug(Argument::type('string'))->shouldNotHaveBeenCalled();
    }

    function it_creates_product_translation_if_missing(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductTranslationInterface $fallbackProductTranslation,
        ProductTranslationInterface $newProductTranslation,
        FactoryInterface $productTranslationFactory
    ) {
        $productVariant->getProduct()->willReturn($product);
        $product->getTranslation('it_IT')->willReturn($fallbackProductTranslation);
        $fallbackProductTranslation->getLocale()->willReturn('en_US');
        $productTranslationFactory->createNew()->willReturn($newProductTranslation);
        $newProductTranslation->getSlug()->willReturn(null);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE, [['locale' => 'it_IT', 'scope' => null, 'data' => self::VALUE_TO_SLUGIFY]]);

        $productTranslationFactory->createNew()->shouldHaveBeenCalled();
        $newProductTranslation->setLocale('it_IT')->shouldHaveBeenCalled();
        $product->addTranslation($newProductTranslation)->shouldHaveBeenCalled();
        $newProductTranslation->setSlug(self::SLUGIFIED_VALUE)->shouldHaveBeenCalled();
    }

    function it_sets_slug_on_all_product_translations_when_locale_not_specified(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductTranslationInterface $italianProductTranslation,
        ProductTranslationInterface $englishProductTranslation
    ) {
        $productVariant->getProduct()->willReturn($product);
        $italianProductTranslation->getLocale()->willReturn('it_IT');
        $englishProductTranslation->getLocale()->willReturn('en_US');
        $product->getTranslation('it_IT')->willReturn($italianProductTranslation);
        $product->getTranslation('en_US')->willReturn($englishProductTranslation);
        $italianProductTranslation->getSlug()->willReturn(null);
        $englishProductTranslation->getSlug()->willReturn(null);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE, [['locale' => null, 'scope' => null, 'data' => self::VALUE_TO_SLUGIFY]]);

        $italianProductTranslation->setSlug(self::SLUGIFIED_VALUE)->shouldHaveBeenCalled();
        $englishProductTranslation->setSlug(self::SLUGIFIED_VALUE)->shouldHaveBeenCalled();
    }

    function it_avoid_to_set_duplicated_slug_on_product_translation(
        ProductVariantInterface $productVariant,
        ProductInterface $product,
        ProductTranslationInterface $productTranslation,
        RepositoryInterface $productTranslationRepository,
        ProductTranslationInterface $anotherProductTranslation,
        ProductInterface $anotherProduct
    ) {
        $productVariant->getProduct()->willReturn($product);
        $product->getTranslation('en_US')->willReturn($productTranslation);
        $productTranslation->getLocale()->willReturn('en_US');
        $productTranslation->getSlug()->willReturn(null);
        $productTranslationRepository
            ->findOneBy(['slug' => self::SLUGIFIED_VALUE, 'locale' => 'en_US'])
            ->willReturn($anotherProductTranslation);
        $anotherProductTranslation->getTranslatable()->willReturn($anotherProduct);
        $product->getId()->willReturn(1);
        $anotherProduct->getId()->willReturn(2);
        $productTranslationRepository
            ->findOneBy(['slug' => self::SLUGIFIED_VALUE . '-1', 'locale' => 'en_US'])
            ->willReturn(null);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE, [['locale' => 'en_US', 'scope' => null, 'data' => self::VALUE_TO_SLUGIFY]]);

        $productTranslation->setSlug(self::SLUGIFIED_VALUE . '-1')->shouldHaveBeenCalled();
    }
}
