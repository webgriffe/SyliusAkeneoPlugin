<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ChannelPricingValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

class ChannelPricingValueHandlerSpec extends ObjectBehavior
{
    private const AKENEO_ATTRIBUTE = 'akeneo_attribute';

    private const ITALY_CHANNEL_CODE = 'ITALY';

    private const US_CHANNEL_CODE = 'US';

    /** @var ChannelInterface|Collaborator */
    private $italyChannel;

    /** @var ChannelInterface|Collaborator */
    private $usChannel;

    function let(
        FactoryInterface $channelPricingFactory,
        ChannelRepositoryInterface $channelRepository,
        CurrencyInterface $eurCurrency,
        ChannelInterface $italyChannel,
        CurrencyInterface $usdCurrency,
        ChannelInterface $usChannel,
        RepositoryInterface $currencyRepository
    ) {
        $this->italyChannel = $italyChannel;
        $this->usChannel = $usChannel;
        $channelRepository
            ->findBy(['baseCurrency' => $eurCurrency])
            ->willReturn(new ArrayCollection([$this->italyChannel->getWrappedObject()]));
        $channelRepository
            ->findBy(['baseCurrency' => $usdCurrency])
            ->willReturn(new ArrayCollection([$this->usChannel->getWrappedObject()]));
        $currencyRepository->findOneBy(['code' => 'EUR'])->willReturn($eurCurrency);
        $currencyRepository->findOneBy(['code' => 'USD'])->willReturn($usdCurrency);
        $this->italyChannel->getCode()->willReturn(self::ITALY_CHANNEL_CODE);
        $this->usChannel->getCode()->willReturn(self::US_CHANNEL_CODE);
        $this->beConstructedWith($channelPricingFactory, $channelRepository, $currencyRepository, self::AKENEO_ATTRIBUTE);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ChannelPricingValueHandler::class);
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
                        'This channel pricing value handler only supports instances of %s, %s given.',
                        ProductVariantInterface::class,
                        \stdClass::class
                    )
                )
            )
            ->during('handle', [new \stdClass(), self::AKENEO_ATTRIBUTE, []]);
    }

    function it_creates_new_channel_prices_for_the_matching_currency_channels(
        ProductVariantInterface $productVariant,
        ChannelPricingInterface $italianChannelPricing,
        ChannelPricingInterface $usChannelPricing,
        FactoryInterface $channelPricingFactory
    ) {
        $value = [
            [
                'locale' => null,
                'scope' => null,
                'data' => [
                    [
                        'amount' => '29.99',
                        'currency' => 'EUR',
                    ],
                    [
                        'amount' => '31.99',
                        'currency' => 'USD',
                    ],
                ],
            ],
        ];
        $channelPricingFactory->createNew()->willReturn($italianChannelPricing, $usChannelPricing);
        /** @noinspection PhpParamsInspection */
        $productVariant->getChannelPricingForChannel(Argument::any())->willReturn(null);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE, $value);

        $channelPricingFactory->createNew()->shouldHaveBeenCalledTimes(2);
        $productVariant->addChannelPricing($italianChannelPricing)->shouldHaveBeenCalled();
        $productVariant->addChannelPricing($usChannelPricing)->shouldHaveBeenCalled();
        $italianChannelPricing->setPrice(2999)->shouldHaveBeenCalled();
        $italianChannelPricing->setChannelCode(self::ITALY_CHANNEL_CODE)->shouldHaveBeenCalled();
        $usChannelPricing->setPrice(3199)->shouldHaveBeenCalled();
        $usChannelPricing->setChannelCode(self::US_CHANNEL_CODE)->shouldHaveBeenCalled();
    }

    function it_updates_existent_channel_prices_for_the_matching_currency_channels(
        ProductVariantInterface $productVariant,
        ChannelPricingInterface $italianChannelPricing,
        ChannelPricingInterface $usChannelPricing,
        FactoryInterface $channelPricingFactory
    ) {
        $value = [
            [
                'locale' => null,
                'scope' => null,
                'data' => [
                    [
                        'amount' => '29.99',
                        'currency' => 'EUR',
                    ],
                    [
                        'amount' => '31.99',
                        'currency' => 'USD',
                    ],
                ],
            ],
        ];
        $productVariant->getChannelPricingForChannel($this->italyChannel)->willReturn($italianChannelPricing);
        $productVariant->getChannelPricingForChannel($this->usChannel)->willReturn($usChannelPricing);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE, $value);

        $channelPricingFactory->createNew()->shouldNotHaveBeenCalled();
        $italianChannelPricing->setPrice(2999)->shouldHaveBeenCalled();
        /** @noinspection PhpStrictTypeCheckingInspection */
        $italianChannelPricing->setChannelCode(Argument::type('string'))->shouldNotHaveBeenCalled();
        $usChannelPricing->setPrice(3199)->shouldHaveBeenCalled();
        /** @noinspection PhpStrictTypeCheckingInspection */
        $usChannelPricing->setChannelCode(Argument::type('string'))->shouldNotHaveBeenCalled();
    }
}
