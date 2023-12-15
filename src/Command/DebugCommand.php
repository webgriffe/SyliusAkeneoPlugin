<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\AttributeValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ChannelPricingValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\FileAttributeValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImageValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ImmutableSlugValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\MetricPropertyValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ProductOptionValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\TranslatablePropertyValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlersResolverInterface;

/**
 * @psalm-type AkeneoAttribute array{code: string, type: string, labels: array<string, ?string>}
 */
final class DebugCommand extends Command
{
    protected static $defaultName = 'webgriffe:akeneo:debug';

    /**
     * @param RepositoryInterface<ProductAttributeInterface> $productAttributeRepository
     */
    public function __construct(
        private AkeneoPimClientInterface $akeneoPimClient,
        private RepositoryInterface $productAttributeRepository,
        private ProductOptionRepositoryInterface $productOptionRepository,
        private ContainerInterface $container,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var AkeneoAttribute[] $akeneoAttributes */
        $akeneoAttributes = $this->akeneoPimClient->getAttributeApi()->all();
        $rows = [];
        /** @var array{type: string, options: array, priority: int} $valueHandlers */
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
            $syliusProductAttribute = $this->productAttributeRepository->findOneBy(['code' => $akeneoAttributeCode]);
            $syliusProductOption = $this->productOptionRepository->findOneBy(['code' => $akeneoAttributeCode]);

            $willBeImportedAsSyliusAttribute = $syliusProductAttribute instanceof ProductAttributeInterface && $isGenericAttributeValueHandlerEnabled;
            $willBeImportedAsSyliusOption = $syliusProductOption instanceof ProductOptionInterface && $isProductOptionValueHandlerEnabled;
            $properties = $this->resolveProperties($valueHandlers, $akeneoAttributeCode);
            $compatibleValueHandlers = $this->resolveValueHandlers($valueHandlers, $akeneoAttributeCode);
            $willBeImported = $willBeImportedAsSyliusAttribute || $willBeImportedAsSyliusOption || count($compatibleValueHandlers) > 0;

            $rows[] = [
                $akeneoAttributeCode,
                $willBeImportedAsSyliusAttribute ? ($willBeImportedAsSyliusOption ? '<error>Yes</error>' : 'Yes') : '',
                $willBeImportedAsSyliusOption ? 'Yes' : '',
                'TODO',
                implode(', ', $properties),
                implode(', ', $compatibleValueHandlers),
                $willBeImported ? 'Yes' : '<error>No</error>',
            ];
        }

        $table = new Table($output);
        $table
            ->setHeaders([
                'Akeneo attribute code',
                'Will be imported as relative Sylius attribute?',
                'Will be imported as relative Sylius option?',
                'Will be imported on Sylius property? TODO',
                'Compatible value handlers',
                'Will be imported?',
            ])
            ->setRows($rows)
        ;
        $table->render();

        return Command::SUCCESS;
    }

    /**
     * @param ValueHandlerInterface[] $valueHandlers
     *
     * @return string[]
     */
    private function resolveValueHandlers(array $valueHandlers, string $akeneoAttributeCode): array
    {
        /** @var array $valueHandlers */
        $supportedValueHandlers = [];
        foreach ($valueHandlers as $valueHandler) {
            # if ($valueHandler['type'] === 'generic_attribute') {
            #     $supportedValueHandlers[] = AttributeValueHandler::class;
            #     continue;
            # }
            # if ($valueHandler['type'] === 'product_option') {
            #     $supportedValueHandlers[] = ProductOptionValueHandler::class;
            #     continue;
            # }
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
        }
        $supportedValueHandlers = array_unique($supportedValueHandlers);

        return $supportedValueHandlers;
    }
}
