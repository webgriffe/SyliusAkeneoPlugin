<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Config\MonologConfig;

return static function (MonologConfig $monolog): void {
    $monolog->channels(['webgriffe_sylius_akeneo_plugin']);
};
