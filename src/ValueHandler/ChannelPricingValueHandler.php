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

        foreach ($value[0]['data'] as $currencyPrice) {
            $currencyCode = $currencyPrice['currency'];
            $price = $currencyPrice['amount'];
            $currency = $this->currencyRepository->findOneBy(['code' => $currencyCode]);
            if ($currency === null) {
                // todo: log?
                continue;
            }
            Assert::isInstanceOf($currency, CurrencyInterface::class);
            /** @var CurrencyInterface $currency */

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
                Assert::isInstanceOf($channelPricing, ChannelPricingInterface::class);
                $channelPricing->setPrice((int) round($price * 100));
                if ($isNewChannelPricing) {
                    $subject->addChannelPricing($channelPricing);
                }
            }
        }
    }
}
