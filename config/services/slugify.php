<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Cocur\Slugify\Slugify;
use Cocur\Slugify\SlugifyInterface;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.slugify', Slugify::class);

    $services->alias(SlugifyInterface::class, 'webgriffe_sylius_akeneo.slugify');
};
