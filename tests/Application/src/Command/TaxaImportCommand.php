<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Sylius\Command;

use League\Csv\Reader;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Taxonomy\Factory\TaxonFactoryInterface;
use Sylius\Component\Taxonomy\Generator\TaxonSlugGeneratorInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

final class TaxaImportCommand extends Command
{
    protected static $defaultName = 'app:taxa-import';

    public function __construct(
        private TaxonFactoryInterface $taxonFactory,
        private TaxonRepositoryInterface $taxonRepository,
        private FactoryInterface $taxonTranslationFactory,
        private TaxonSlugGeneratorInterface $taxonSlugGenerator,
        private LocaleProviderInterface $localeProvider,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $description = <<<TEXT
Import taxons from a CSV file with the following columns:
    * code (Taxon code)
    * parent (Parent taxon code)
    * label-* (Many columns with taxon name in the locale code specified with the *)
TEXT;

        $this
            ->addArgument('csvFile', InputArgument::REQUIRED)
            ->addOption('stripFromSlug', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->setDescription($description)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $csvFile = $input->getArgument('csvFile');
        Assert::string($csvFile);
        $stripFromSlug = $input->getOption('stripFromSlug');
        Assert::isArray($stripFromSlug);
        $csv = Reader::createFromPath($csvFile);
        $csv->setDelimiter(';');
        $csv->setHeaderOffset(0);

        foreach ($csv as $record) {
            $code = $record['code'];
            $parentCode = empty($record['parent']) ? null : $record['parent'];
            $alreadyExistentTaxon = $this->taxonRepository->findOneBy(['code' => $code]);
            if ($alreadyExistentTaxon instanceof ResourceInterface) {
                $output->writeln("Taxon <info>$code</info> already exists, skipping...");

                continue;
            }

            /** @var TaxonInterface $newTaxon */
            $newTaxon = $this->taxonFactory->createNew();
            if ($parentCode !== null) {
                /** @var TaxonInterface|null $parentTaxon */
                $parentTaxon = $this->taxonRepository->findOneBy(['code' => $parentCode]);
                Assert::isInstanceOf($parentTaxon, TaxonInterface::class);
                $newTaxon = $this->taxonFactory->createForParent($parentTaxon);
            }

            $newTaxon->setCode($code);
            foreach ($this->localeProvider->getAvailableLocalesCodes() as $localeCode) {
                if (empty($record['label-' . $localeCode])) {
                    continue;
                }
                $localizedName = $record['label-' . $localeCode];
                /** @var TaxonTranslationInterface $newTaxonTranslation */
                $newTaxonTranslation = $this->taxonTranslationFactory->createNew();
                Assert::isInstanceOf($newTaxonTranslation, TaxonTranslationInterface::class);
                $newTaxonTranslation->setLocale($localeCode);
                $newTaxonTranslation->setName($localizedName);
                $newTaxon->addTranslation($newTaxonTranslation);
                $slug = $this->taxonSlugGenerator->generate($newTaxon, $localeCode);
                $slug = str_replace($stripFromSlug, '', $slug);
                $newTaxonTranslation->setSlug($slug);
            }
            $this->taxonRepository->add($newTaxon);
            $output->writeln("Taxon <info>$code</info> with parent <info>$parentCode</info> has been created.");
        }

        return 0;
    }
}
