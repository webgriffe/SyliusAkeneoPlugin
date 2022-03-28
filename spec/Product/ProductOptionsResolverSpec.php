<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\Product;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\FamilyVariantApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductModelApiInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PhpSpec\ObjectBehavior;
use RuntimeException;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\Product\ProductOptionsResolver;
use Webgriffe\SyliusAkeneoPlugin\Product\ProductOptionsResolverInterface;

class ProductOptionsResolverSpec extends ObjectBehavior
{
    private const PRODUCT_MODEL_CODE = 'product_model_code';

    private const FAMILY_CODE = 'family_code';

    private const FAMILY_VARIANT_CODE = 'family_variant_code';

    private const ATTRIBUTE_CODE = 'attribute_code';

    private const ITALIAN_LABEL = 'Italian label';

    private const ENGLISH_LABEL = 'English label';

    public function let(
        AkeneoPimClientInterface $apiClient,
        ProductModelApiInterface $productModelApi,
        FamilyVariantApiInterface $familyVariantApi,
        AttributeApiInterface $attributeApi,
        ProductOptionRepositoryInterface $productOptionRepository,
        FactoryInterface $productOptionFactory,
        ProductOptionInterface $newProductOption,
        ProductOptionTranslationInterface $englishProductOptionTranslation,
        FactoryInterface $productOptionTranslationFactory
    ): void {
        $apiClient->getProductModelApi()->willReturn($productModelApi);
        $apiClient->getFamilyVariantApi()->willReturn($familyVariantApi);
        $apiClient->getAttributeApi()->willReturn($attributeApi);
        $productModelApi->get(self::PRODUCT_MODEL_CODE)->willReturn(
            ['family' => self::FAMILY_CODE, 'family_variant' => self::FAMILY_VARIANT_CODE]
        );
        $familyVariantApi->get(self::FAMILY_CODE, self::FAMILY_VARIANT_CODE)->willReturn(
            ['variant_attribute_sets' => [['axes' => [self::ATTRIBUTE_CODE]]]]
        );
        $attributeApi->get(self::ATTRIBUTE_CODE)->willReturn(
            ['labels' => ['it_IT' => self::ITALIAN_LABEL, 'en_US' => self::ENGLISH_LABEL]]
        );
        $newProductOption->getTranslation('en_US')->willReturn($englishProductOptionTranslation);
        $newProductOption->getTranslation('it_IT')->willReturn($englishProductOptionTranslation);
        $this->beConstructedWith(
            $apiClient,
            $productOptionRepository,
            $productOptionFactory,
            $productOptionTranslationFactory
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ProductOptionsResolver::class);
    }

    public function it_implements_product_options_resolver_interface(): void
    {
        $this->shouldHaveType(ProductOptionsResolverInterface::class);
    }

    public function it_throws_an_exception_if_akeneo_product_has_no_parent(): void
    {
        $this
            ->shouldThrow(
                new RuntimeException(
                    sprintf(
                        'Cannot resolve product options for Akeneo product "%s" because it does not belong to any ' .
                        'product model.',
                        'identifier'
                    )
                )
            )
            ->during('resolve', [['identifier' => 'identifier']])
        ;
    }

    public function it_throws_an_exception_if_product_model_cannot_be_found_on_akeneo(ProductModelApiInterface $productModelApi): void
    {
        $productModelApi->get('not_existent_model')->willThrow(
            new HttpException('Not found', new Request('GET', '/'), new Response(404))
        );

        $this
            ->shouldThrow(
                new RuntimeException(sprintf('Cannot find product model "%s" on Akeneo.', 'not_existent_model'))
            )
            ->during('resolve', [['parent' => 'not_existent_model']])
        ;
    }

    public function it_throws_an_exception_if_family_variant_cannot_be_found_on_akeneo(
        ProductModelApiInterface $productModelApi,
        FamilyVariantApiInterface $familyVariantApi
    ): void {
        $productModelApi
            ->get('model_with_not_existent_family_variant')
            ->willReturn(['family' => 'not_existent', 'family_variant' => 'not_existent'])
        ;
        $familyVariantApi->get('not_existent', 'not_existent')->willThrow(
            new HttpException('Not found', new Request('GET', '/'), new Response(404))
        );
        $this
            ->shouldThrow(
                new RuntimeException(
                    sprintf(
                        'Cannot find family variant "%s" within family "%s" on Akeneo.',
                        'not_existent',
                        'not_existent'
                    )
                )
            )
            ->during('resolve', [['parent' => 'model_with_not_existent_family_variant']])
        ;
    }

    public function it_resolve_already_existent_product_option(
        ProductOptionRepositoryInterface $productOptionRepository,
        ProductOptionInterface $alreadyExistentProductOption
    ): void {
        $productOptionRepository->findOneBy(['code' => self::ATTRIBUTE_CODE])->willReturn(
            $alreadyExistentProductOption
        );

        $this->resolve(['parent' => self::PRODUCT_MODEL_CODE])->shouldReturn([$alreadyExistentProductOption]);
    }

    public function it_throws_an_exception_if_attribute_does_not_exists_on_akeneo_while_creating_new_product_option(
        ProductOptionRepositoryInterface $productOptionRepository,
        FactoryInterface $productOptionFactory,
        ProductOptionInterface $newProductOption,
        AttributeApiInterface $attributeApi
    ): void {
        $productOptionRepository->findOneBy(['code' => self::ATTRIBUTE_CODE])->willReturn(null);
        $productOptionFactory->createNew()->willReturn($newProductOption);
        $attributeApi->get(self::ATTRIBUTE_CODE)->willThrow(
            new HttpException('Not found', new Request('GET', '/'), new Response(404))
        );

        $this
            ->shouldThrow(
                new RuntimeException(
                    sprintf(
                        'Cannot resolve product options for product "%s" because one of its variant attributes, ' .
                        '"%s", cannot be found on Akeneo.',
                        'identifier',
                        self::ATTRIBUTE_CODE
                    )
                )
            )
            ->during('resolve', [['identifier' => 'identifier', 'parent' => self::PRODUCT_MODEL_CODE]])
        ;
        $newProductOption->setCode(self::ATTRIBUTE_CODE)->shouldHaveBeenCalled();
        $newProductOption->setPosition(0)->shouldHaveBeenCalled();
    }

    public function it_creates_new_product_option_with_their_translations_if_it_does_not_already_exists(
        ProductOptionRepositoryInterface $productOptionRepository,
        FactoryInterface $productOptionFactory,
        ProductOptionInterface $newProductOption,
        ProductOptionTranslationInterface $englishProductOptionTranslation,
        ProductOptionTranslationInterface $italianProductOptionTranslation,
        FactoryInterface $productOptionTranslationFactory
    ): void {
        $productOptionRepository->findOneBy(['code' => self::ATTRIBUTE_CODE])->willReturn(null);
        $productOptionFactory->createNew()->willReturn($newProductOption);
        $englishProductOptionTranslation->getLocale()->willReturn('en_US');
        $newProductOption->getTranslation('en_US')->willReturn($englishProductOptionTranslation);
        $newProductOption->getTranslation('it_IT')->willReturn($englishProductOptionTranslation);
        $productOptionTranslationFactory->createNew()->willReturn($italianProductOptionTranslation);

        $this->resolve(['parent' => self::PRODUCT_MODEL_CODE])->shouldReturn([$newProductOption]);

        $newProductOption->setCode(self::ATTRIBUTE_CODE)->shouldHaveBeenCalled();
        $newProductOption->setPosition(0)->shouldHaveBeenCalled();
        $italianProductOptionTranslation->setLocale('it_IT')->shouldHaveBeenCalled();
        $newProductOption->addTranslation($italianProductOptionTranslation)->shouldHaveBeenCalled();
        $englishProductOptionTranslation->setName(self::ENGLISH_LABEL)->shouldHaveBeenCalled();
        $italianProductOptionTranslation->setName(self::ITALIAN_LABEL)->shouldHaveBeenCalled();
        $productOptionRepository->add($newProductOption)->shouldHaveBeenCalled();
    }
}
