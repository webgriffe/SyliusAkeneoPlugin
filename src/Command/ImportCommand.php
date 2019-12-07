<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Command;

use Webgriffe\SyliusAkeneoPlugin\ImporterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'webgriffe:akeneo:import';
    /**
     * @var ImporterInterface
     */
    private $productModelImporter;

    public function __construct(ImporterInterface $productModelImporter)
    {
        $this->productModelImporter = $productModelImporter;
        parent::__construct();
    }


    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $productModelsToImport = [];
        // TODO fetch product models to import from queue and put in $productModelsToImport
        foreach ($productModelsToImport as $identifier) {
            $this->productModelImporter->import($identifier);
        }
    }
}
