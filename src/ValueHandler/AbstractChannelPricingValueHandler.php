<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

abstract class AbstractChannelPricingValueHandler implements ValueHandlerInterface
{
    /** @var FactoryInterface */
    protected $channelPricingFactory;

    /** @var ChannelRepositoryInterface */
    protected $channelRepository;

    /** @var RepositoryInterface */
    protected $currencyRepository;

    /** @var string */
    protected $akeneoAttribute;

    public function __construct(
        FactoryInterface $channelPricingFactory,
        ChannelRepositoryInterface $channelRepository,
        RepositoryInterface $currencyRepository,
        string $akeneoAttribute
    ) {
        $this->channelPricingFactory = $channelPricingFactory;
        $this->channelRepository = $channelRepository;
        $this->currencyRepository = $currencyRepository;
        $this->akeneoAttribute = $akeneoAttribute;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($subject, string $attribute, array $value): bool
    {
        return $subject instanceof ProductVariantInterface && $attribute === $this->akeneoAttribute;
    }

    /**
     * {@inheritdoc}
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
        $this->setPrices($subject, $currenciesPrices);
    }

    /**
     * @param array<array-key, array{currency: string, amount: float}> $currenciesPrices
     */
    protected function setPrices(ProductVariantInterface $variant, array $currenciesPrices): void
    {
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
                $channelPricing = $variant->getChannelPricingForChannel($channel);
                if ($channelPricing === null) {
                    $isNewChannelPricing = true;
                    /** @var ChannelPricingInterface $channelPricing */
                    $channelPricing = $this->channelPricingFactory->createNew();
                    $channelPricing->setChannelCode($channel->getCode());
                }

                $this->setPrice($channelPricing, (int) round($price * 100));

                if ($isNewChannelPricing) {
                    $variant->addChannelPricing($channelPricing);
                }
            }
        }
    }

    abstract protected function setPrice(ChannelPricingInterface $channelPricing, int $price): void;
}
