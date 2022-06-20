<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use InvalidArgumentException;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class ChannelPricingValueHandler implements ValueHandlerInterface
{
    public function __construct(
        private FactoryInterface $channelPricingFactory,
        private ChannelRepositoryInterface $channelRepository,
        private RepositoryInterface $currencyRepository,
        private string $akeneoAttribute,
        private PropertyAccessorInterface $propertyAccessor,
        private string $syliusPropertyPath = 'price',
    ) {
    }

    public function supports($subject, string $attribute, array $value): bool
    {
        return $subject instanceof ProductVariantInterface && $attribute === $this->akeneoAttribute;
    }

    public function handle($subject, string $attribute, array $value): void
    {
        if (!$subject instanceof ProductVariantInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'This channel pricing value handler only supports instances of %s, %s given.',
                    ProductVariantInterface::class,
                    get_debug_type($subject),
                ),
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

                $this->propertyAccessor->setValue($channelPricing, $this->syliusPropertyPath, (int) round($price * 100));
                Assert::isInstanceOf($channelPricing, ChannelPricingInterface::class);
                if ($isNewChannelPricing) {
                    $subject->addChannelPricing($channelPricing);
                }
            }
        }
    }
}
