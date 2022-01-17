<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

final class ChannelPricingValueHandler implements ValueHandlerInterface
{
    /** @var FactoryInterface */
    private $channelPricingFactory;

    /** @var ChannelRepositoryInterface */
    private $channelRepository;

    /** @var RepositoryInterface */
    private $currencyRepository;

    /** @var string */
    private $akeneoAttribute;

    /** @var string */
    private $syliusPropertyPath;

    /** @var PropertyAccessorInterface|null */
    private $propertyAccessor;

    public function __construct(
        FactoryInterface $channelPricingFactory,
        ChannelRepositoryInterface $channelRepository,
        RepositoryInterface $currencyRepository,
        string $akeneoAttribute,
        PropertyAccessorInterface $propertyAccessor = null,
        string $syliusPropertyPath = 'price'
    ) {
        $this->channelPricingFactory = $channelPricingFactory;
        $this->channelRepository = $channelRepository;
        $this->currencyRepository = $currencyRepository;
        $this->akeneoAttribute = $akeneoAttribute;
        if ($propertyAccessor === null) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                '1.12',
                'Not passing a property accessor to "%s" is deprecated and will be removed in %s.',
                __CLASS__,
                '2.0'
            );
        }
        $this->propertyAccessor = $propertyAccessor;
        $this->syliusPropertyPath = $syliusPropertyPath;
    }

    /**
     * @inheritdoc
     */
    public function supports($subject, string $attribute, array $value): bool
    {
        return $subject instanceof ProductVariantInterface && $attribute === $this->akeneoAttribute;
    }

    /**
     * @inheritdoc
     */
    public function handle($subject, string $attribute, array $value): void
    {
        if (!$subject instanceof ProductVariantInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This channel pricing value handler only supports instances of %s, %s given.',
                    ProductVariantInterface::class,
                    is_object($subject) ? get_class($subject) : gettype($subject)
                )
            );
        }

        /** @var array<array-key, array{currency: string, amount: float}> $currenciesPrices */
        $currenciesPrices = $value[0]['data'] ?? [];
        foreach ($currenciesPrices as $currencyPrice) {
            $currencyCode = $currencyPrice['currency'];
            $price = $currencyPrice['amount'];
            /** @var CurrencyInterface|null $currency */
            $currency = $this->currencyRepository->findOneBy(['code' => $currencyCode]);
            if ($currency === null) {
                continue;
            }

            /** @var ChannelInterface[] $channels */
            $channels = $this->channelRepository->findBy(['baseCurrency' => $currency]);
            foreach ($channels as $channel) {
                $isNewChannelPricing = false;
                $channelPricing = $subject->getChannelPricingForChannel($channel);
                if ($channelPricing === null) {
                    $isNewChannelPricing = true;
                    /** @var ChannelPricingInterface $channelPricing */
                    $channelPricing = $this->channelPricingFactory->createNew();
                    $channelPricing->setChannelCode($channel->getCode());
                }

                if ($this->propertyAccessor === null) {
                    $channelPricing->setPrice((int) round($price * 100));
                } else {
                    $this->propertyAccessor->setValue($channelPricing, $this->syliusPropertyPath, (int) round($price * 100));
                    Assert::isInstanceOf($channelPricing, ChannelPricingInterface::class);
                }
                if ($isNewChannelPricing) {
                    $subject->addChannelPricing($channelPricing);
                }
            }
        }
    }
}
