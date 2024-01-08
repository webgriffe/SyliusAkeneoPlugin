<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @psalm-type AkeneoAttribute array{code: string, type: string, labels: array<string, ?string>}
 */
final class DebugCommand extends Command
{
    protected static $defaultName = 'webgriffe:akeneo:debug';

    /** @var array<string, ProductAttributeInterface> */
    private array $syliusAttributes = [];

    /** @var array<string, ProductOptionInterface> */
    private array $syliusOptions = [];

    /**
     * @param RepositoryInterface<ProductAttributeInterface> $productAttributeRepository
     * @param FactoryInterface<ChannelPricingInterface> $channelPricingFactory
     * @param FactoryInterface<ProductTranslationInterface> $productTranslationFactory
     * @param FactoryInterface<ProductVariantTranslationInterface> $productVariantTranslationFactory
     */
    public function __construct(
        private AkeneoPimClientInterface $akeneoPimClient,
        private RepositoryInterface $productAttributeRepository,
        private ProductOptionRepositoryInterface $productOptionRepository,
        private ContainerInterface $container,
        private PropertyAccessorInterface $propertyAccessor,
        private ProductFactoryInterface $productFactory,
        private ProductVariantFactoryInterface $productVariantFactory,
        private FactoryInterface $channelPricingFactory,
        private FactoryInterface $productTranslationFactory,
        private FactoryInterface $productVariantTranslationFactory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->productAttributeRepository->findAll() as $productAttribute) {
            $this->syliusAttributes[(string) $productAttribute->getCode()] = $productAttribute;
        }
        /** @var ProductOptionInterface $productOption */
        foreach ($this->productOptionRepository->findAll() as $productOption) {
            $this->syliusOptions[(string) $productOption->getCode()] = $productOption;
        }
        /** @var AkeneoAttribute[] $akeneoAttributes */
        $akeneoAttributes = $this->akeneoPimClient->getAttributeApi()->all();
        $rows = [];
        /** @var array<array-key, array{type: string, options: array, priority: int}> $valueHandlers */
        $valueHandlers = $this->container->getParameter('webgriffe_sylius_akeneo_plugin.value_handlers.product');
        $isGenericAttributeValueHandlerEnabled = false;
        $isProductOptionValueHandlerEnabled = false;
        foreach ($valueHandlers as $valueHandler) {
            if ($valueHandler['type'] === 'generic_attribute') {
                $isGenericAttributeValueHandlerEnabled = true;

                continue;
            }
            if ($valueHandler['type'] === 'product_option') {
                $isProductOptionValueHandlerEnabled = true;

                continue;
            }
        }
        foreach ($akeneoAttributes as $akeneoAttribute) {
            $akeneoAttributeCode = $akeneoAttribute['code'];
            $syliusProductAttribute = $this->syliusAttributes[$akeneoAttributeCode] ?? null;
            $syliusProductOption = $this->syliusOptions[$akeneoAttributeCode] ?? null;

            $willBeImportedAsSyliusAttribute = $syliusProductAttribute instanceof ProductAttributeInterface && $isGenericAttributeValueHandlerEnabled;
            $willBeImportedAsSyliusOption = $syliusProductOption instanceof ProductOptionInterface && $isProductOptionValueHandlerEnabled;
            $properties = $this->resolveProperties($valueHandlers, $akeneoAttributeCode);
            $compatibleValueHandlers = $this->resolveValueHandlers($valueHandlers, $akeneoAttributeCode);
            $willBeImported = $willBeImportedAsSyliusAttribute || $willBeImportedAsSyliusOption || count($compatibleValueHandlers) > 0;

            $rowspan = max(count($properties), count($compatibleValueHandlers));

            $rows[] = [
                new TableCell($willBeImported ? $akeneoAttributeCode : '<error>' . $akeneoAttributeCode . '</error>', ['rowspan' => $rowspan]),
                new TableCell($willBeImportedAsSyliusAttribute ? 'Yes' : '', ['rowspan' => $rowspan]),
                new TableCell($willBeImportedAsSyliusOption ? 'Yes' : '', ['rowspan' => $rowspan]),
                new TableCell(array_key_exists(0, $properties) ? $properties[0] : ''),
                new TableCell(array_key_exists(0, $compatibleValueHandlers) ? $compatibleValueHandlers[0] : ''),
                new TableCell($willBeImported ? 'Yes' : '<error>No</error>', ['rowspan' => $rowspan]),
            ];
            for ($i = 1; $i < $rowspan; ++$i) {
                $rows[] = [
                    new TableCell(array_key_exists($i, $properties) ? $properties[$i] : ''),
                    new TableCell(array_key_exists($i, $compatibleValueHandlers) ? $compatibleValueHandlers[$i] : ''),
                ];
            }
        }

        $table = new Table($output);
        $table
            ->setHeaders([
                'Akeneo attribute code',
                'Will be imported as Sylius attribute?',
                'Will be imported as Sylius option?',
                'Will be imported on Sylius property?',
                'Compatible value handlers',
                'Will be imported?',
            ])
            ->setRows($rows)
        ;
        $table->render();

        return Command::SUCCESS;
    }

    /**
     * @param array<array-key, array{type: string, options: array, priority: int}> $valueHandlers
     *
     * @return string[]
     */
    private function resolveValueHandlers(array $valueHandlers, string $akeneoAttributeCode): array
    {
        $supportedValueHandlers = [];
        foreach ($valueHandlers as $valueHandler) {
            if ($valueHandler['type'] === 'generic_attribute') {
                if (array_key_exists($akeneoAttributeCode, $this->syliusAttributes)) {
                    $supportedValueHandlers[] = 'generic_attribute';
                }

                continue;
            }
            if ($valueHandler['type'] === 'product_option') {
                if (array_key_exists($akeneoAttributeCode, $this->syliusOptions)) {
                    $supportedValueHandlers[] = 'product_option';
                }

                continue;
            }
            if ($valueHandler['type'] === 'translatable_property') {
                if ($valueHandler['options']['$akeneoAttributeCode'] !== $akeneoAttributeCode) {
                    continue;
                }
                $supportedValueHandlers[] = 'translatable_property';

                continue;
            }
            if ($valueHandler['type'] === 'immutable_slug') {
                if ($valueHandler['options']['$akeneoAttributeToSlugify'] !== $akeneoAttributeCode) {
                    continue;
                }
                $supportedValueHandlers[] = 'immutable_slug';

                continue;
            }
            if ($valueHandler['type'] === 'image') {
                if ($valueHandler['options']['$akeneoAttributeCode'] !== $akeneoAttributeCode) {
                    continue;
                }
                $supportedValueHandlers[] = 'image';

                continue;
            }
            if ($valueHandler['type'] === 'channel_pricing') {
                if ($valueHandler['options']['$akeneoAttribute'] !== $akeneoAttributeCode) {
                    continue;
                }
                $supportedValueHandlers[] = 'channel_pricing';

                continue;
            }
            if ($valueHandler['type'] === 'file_attribute') {
                if ($valueHandler['options']['$akeneoAttributeCode'] !== $akeneoAttributeCode) {
                    continue;
                }
                $supportedValueHandlers[] = 'file_attribute';

                continue;
            }
            if ($valueHandler['type'] === 'metric_property') {
                if ($valueHandler['options']['$akeneoAttributeCode'] !== $akeneoAttributeCode) {
                    continue;
                }
                $supportedValueHandlers[] = 'metric_property';
            }
            if ($valueHandler['type'] === 'generic_property') {
                if ($valueHandler['options']['$akeneoAttributeCode'] !== $akeneoAttributeCode) {
                    continue;
                }
                $supportedValueHandlers[] = 'generic_property';
            }
        }

        return array_unique($supportedValueHandlers);
    }

    /**
     * @param array<array-key, array{type: string, options: array, priority: int}> $valueHandlers
     *
     * @return string[]
     */
    private function resolveProperties(array $valueHandlers, string $akeneoAttributeCode): array
    {
        $properties = [];
        foreach ($valueHandlers as $valueHandler) {
            if ($valueHandler['type'] === 'generic_property') {
                if ($valueHandler['options']['$akeneoAttributeCode'] !== $akeneoAttributeCode) {
                    continue;
                }
                /** @var string $propertyPath */
                $propertyPath = $valueHandler['options']['$propertyPath'];
                if ($this->propertyAccessor->isWritable($this->productFactory->createNew(), $propertyPath)) {
                    $properties[] = 'ProductInterface' . '::$' . $propertyPath;
                }
                if ($this->propertyAccessor->isWritable($this->productVariantFactory->createNew(), $propertyPath)) {
                    $properties[] = 'ProductVariantInterface' . '::$' . $propertyPath;
                }

                continue;
            }
            if ($valueHandler['type'] === 'channel_pricing') {
                if ($valueHandler['options']['$akeneoAttribute'] !== $akeneoAttributeCode) {
                    continue;
                }
                /** @var string $propertyPath */
                $propertyPath = $valueHandler['options']['$syliusPropertyPath'];
                if ($this->propertyAccessor->isWritable($this->channelPricingFactory->createNew(), $propertyPath)) {
                    $properties[] = 'ChannelPricingInterface' . '::$' . $propertyPath;
                }

                continue;
            }
            if ($valueHandler['type'] === 'translatable_property') {
                if ($valueHandler['options']['$akeneoAttributeCode'] !== $akeneoAttributeCode) {
                    continue;
                }
                /** @var string $propertyPath */
                $propertyPath = $valueHandler['options']['$translationPropertyPath'];
                if ($this->propertyAccessor->isWritable($this->productTranslationFactory->createNew(), $propertyPath)) {
                    $properties[] = 'ProductTranslationInterface' . '::$' . $propertyPath;
                }
                if ($this->propertyAccessor->isWritable($this->productVariantTranslationFactory->createNew(), $propertyPath)) {
                    $properties[] = 'ProductVariantTranslationInterface' . '::$' . $propertyPath;
                }

                continue;
            }
            if ($valueHandler['type'] === 'immutable_slug') {
                if ($valueHandler['options']['$akeneoAttributeToSlugify'] !== $akeneoAttributeCode) {
                    continue;
                }
                $properties[] = 'ProductTranslationInterface::$slug';

                continue;
            }
            if ($valueHandler['type'] === 'image') {
                if ($valueHandler['options']['$akeneoAttributeCode'] !== $akeneoAttributeCode) {
                    continue;
                }
                $properties[] = 'ProductImageInterface::$file';

                continue;
            }
            if ($valueHandler['type'] === 'metric_property') {
                if ($valueHandler['options']['$akeneoAttributeCode'] !== $akeneoAttributeCode) {
                    continue;
                }
                /** @var string $propertyPath */
                $propertyPath = $valueHandler['options']['$propertyPath'];
                if ($this->propertyAccessor->isWritable($this->productFactory->createNew(), $propertyPath)) {
                    $properties[] = 'ProductInterface' . '::$' . $propertyPath;
                }
                if ($this->propertyAccessor->isWritable($this->productVariantFactory->createNew(), $propertyPath)) {
                    $properties[] = 'ProductVariantInterface' . '::$' . $propertyPath;
                }

                continue;
            }
        }

        return $properties;
    }
}
