<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Sylius\Command;

use League\Csv\Reader;
use Sylius\Component\Attribute\Factory\AttributeFactoryInterface;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Sylius\Component\Product\Model\ProductAttributeTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

final class AttributesImportCommand extends Command
{
    protected static $defaultName = 'app:attributes-import';

    private array $productAttributesTypeMap = [
        'pim_catalog_identifier' => 'text',
        'pim_catalog_simpleselect' => 'select',
        'pim_catalog_text' => 'text',
        'pim_catalog_textarea' => 'textarea',
        'pim_catalog_boolean' => 'checkbox',
        'pim_catalog_image' => 'text',
        'pim_catalog_file' => 'text',
        'pim_catalog_metric' => 'text',
        'pim_catalog_number' => 'integer',
        'pim_catalog_multiselect' => 'select',
        'pim_catalog_date' => 'date',
        'pim_catalog_price_collection' => 'text',
    ];

    public function __construct(
        private AttributeFactoryInterface $attributeFactory,
        private RepositoryInterface $attributeRepository,
        private LocaleProviderInterface $localeProvider,
        private FactoryInterface $attributeTranslationFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $description = <<<TEXT
Import product attributes from a CSV file with the following columns:
    * code (Product Attribute code)
    * label-* (Many columns with taxon name in the locale code specified with the *)
    * type (Akeneo attribute type)
    * sort_order (Akeneo attribute position)
    * localizable (Akeneo attribute localizable)
TEXT;

        $this
            ->addArgument('csvFile', InputArgument::REQUIRED)
            ->setDescription($description)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $csvFile = $input->getArgument('csvFile');
        Assert::string($csvFile);
        $csv = Reader::createFromPath($csvFile);
        $csv->setDelimiter(';');
        $csv->setHeaderOffset(0);

        foreach ($csv as $record) {
            $code = $record['code'];
            $akeneoAttributeType = $record['type'];
            Assert::string($akeneoAttributeType);
            $position = (int) $record['sort_order'];
            $localizable = $record['localizable'] === '1';
            $alreadyExistentAttribute = $this->attributeRepository->findOneBy(['code' => $code]);
            if ($alreadyExistentAttribute) {
                $output->writeln("Product attribute <info>$code</info> already exists, skipping...");

                continue;
            }
            if (!array_key_exists($akeneoAttributeType, $this->productAttributesTypeMap)) {
                $output->writeln("<error>Error</error>: Unable to create product attribute with code <info>$code</info>. Unrecognized attribute type: <info>$akeneoAttributeType</info></info>");

                continue;
            }
            $syliusAttributeType = $this->productAttributesTypeMap[$akeneoAttributeType];

            $newAttribute = $this->attributeFactory->createTyped($syliusAttributeType);
            $newAttribute->setCode($code);
            $newAttribute->setPosition($position);
            $newAttribute->setTranslatable($localizable);
            $isMultiSelect = $akeneoAttributeType === 'pim_catalog_multiselect';
            if ($akeneoAttributeType === 'pim_catalog_simpleselect' || $isMultiSelect) {
                $configuration = [
                    'min' => null,
                    'max' => null,
                    'multiple' => false,
                ];
                if ($isMultiSelect) {
                    $configuration['multiple'] = true;
                }
                $newAttribute->setConfiguration($configuration);
            }
            foreach ($this->localeProvider->getAvailableLocalesCodes() as $localeCode) {
                if (empty($record['label-' . $localeCode])) {
                    continue;
                }
                $localizedName = $record['label-' . $localeCode];
                /** @var ProductAttributeTranslationInterface $newProductAttributeTranslation */
                $newProductAttributeTranslation = $this->attributeTranslationFactory->createNew();
                Assert::isInstanceOf($newProductAttributeTranslation, ProductAttributeTranslationInterface::class);
                $newProductAttributeTranslation->setLocale($localeCode);
                $newProductAttributeTranslation->setName($localizedName);
                $newAttribute->addTranslation($newProductAttributeTranslation);
            }
            $this->attributeRepository->add($newAttribute);
            $output->writeln("Product attribute <info>$code</info> has been created.");
        }

        return Command::SUCCESS;
    }
}
