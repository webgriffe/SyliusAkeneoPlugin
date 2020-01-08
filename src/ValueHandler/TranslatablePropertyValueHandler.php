<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Sylius\Component\Resource\Model\TranslationInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class TranslatablePropertyValueHandler implements ValueHandlerInterface
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var FactoryInterface */
    private $translationFactory;

    /** @var TranslationLocaleProviderInterface */
    private $localeProvider;

    /** @var string */
    private $akeneoAttributeCode;

    /** @var string */
    private $translationPropertyPath;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productTranslationFactory,
        TranslationLocaleProviderInterface $localeProvider,
        string $akeneoAttributeCode,
        string $translationPropertyPath
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->translationFactory = $productTranslationFactory;
        $this->localeProvider = $localeProvider;
        $this->akeneoAttributeCode = $akeneoAttributeCode;
        $this->translationPropertyPath = $translationPropertyPath;
    }

    public function supports($subject, string $attribute, array $value): bool
    {
        return $subject instanceof TranslatableInterface && $attribute === $this->akeneoAttributeCode;
    }

    public function handle($subject, string $attribute, array $value)
    {
        if (!$subject instanceof TranslatableInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This translatable property value handler only support instances of %s, %s given.',
                    TranslatableInterface::class,
                    is_object($subject) ? get_class($subject) : gettype($subject)
                )
            );
        }
        foreach ($value as $item) {
            $localeCode = $item['locale'];
            if (!$localeCode) {
                $this->setValueOnAllTranslations($subject, $item);

                continue;
            }
            $translation = $this->getOrCreateNewProductTranslation($subject, $localeCode);
            $this->propertyAccessor->setValue(
                $translation,
                $this->translationPropertyPath,
                $item['data']
            );
        }
    }

    private function setValueOnAllTranslations(TranslatableInterface $subject, array $value): void
    {
        foreach ($this->localeProvider->getDefinedLocalesCodes() as $localeCode) {
            $translation = $this->getOrCreateNewProductTranslation($subject, $localeCode);
            $this->propertyAccessor->setValue(
                $translation,
                $this->translationPropertyPath,
                $value['data']
            );
        }
    }

    private function getOrCreateNewProductTranslation(
        TranslatableInterface $subject,
        string $localeCode
    ): TranslationInterface {
        $translation = $subject->getTranslation($localeCode);
        if ($translation->getLocale() !== $localeCode) {
            $translation = $this->translationFactory->createNew();
            Assert::isInstanceOf($translation, TranslationInterface::class);
            /** @var TranslationInterface $translation */
            $translation->setLocale($localeCode);
            $subject->addTranslation($translation);
        }

        return $translation;
    }
}
