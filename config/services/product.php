<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\PriorityValueHandlersResolver;
use Webgriffe\SyliusAkeneoPlugin\Product\AllChannelsResolver;
use Webgriffe\SyliusAkeneoPlugin\Product\AlreadyExistingTaxonsResolver;
use Webgriffe\SyliusAkeneoPlugin\Product\ChannelsResolverInterface;
use Webgriffe\SyliusAkeneoPlugin\Product\Importer;
use Webgriffe\SyliusAkeneoPlugin\Product\ProductOptionsResolver;
use Webgriffe\SyliusAkeneoPlugin\Product\ProductOptionsResolverInterface;
use Webgriffe\SyliusAkeneoPlugin\Product\StatusResolver;
use Webgriffe\SyliusAkeneoPlugin\Product\TaxonsResolverInterface;
use Webgriffe\SyliusAkeneoPlugin\Product\Validator;
use Webgriffe\SyliusAkeneoPlugin\Product\VariantStatusResolver;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlersResolverInterface;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.product.value_handlers_resolver', PriorityValueHandlersResolver::class);

    $services->alias(ValueHandlersResolverInterface::class, 'webgriffe_sylius_akeneo.product.value_handlers_resolver');

    $services->set('webgriffe_sylius_akeneo.product.taxons_resolver', AlreadyExistingTaxonsResolver::class)
        ->args([
            service('sylius.repository.taxon'),
        ])
    ;

    $services->alias(TaxonsResolverInterface::class, 'webgriffe_sylius_akeneo.product.taxons_resolver');

    $services->set('webgriffe_sylius_akeneo.product.product_options_resolver', ProductOptionsResolver::class)
        ->args([
            service('webgriffe_sylius_akeneo.api_client'),
            service('sylius.repository.product_option'),
            service('sylius.factory.product_option'),
            service('sylius.factory.product_option_translation'),
        ])
    ;

    $services->alias(ProductOptionsResolverInterface::class, 'webgriffe_sylius_akeneo.product.product_options_resolver');

    $services->set('webgriffe_sylius_akeneo.product.channels_resolver', AllChannelsResolver::class)
        ->args([
            service('sylius.repository.channel'),
        ])
    ;

    $services->alias(ChannelsResolverInterface::class, 'webgriffe_sylius_akeneo.product.channels_resolver');

    $services->set('webgriffe_sylius_akeneo.product.status_resolver', StatusResolver::class);

    $services->set('webgriffe_sylius_akeneo.product.variant_status_resolver', VariantStatusResolver::class);

    $services->set('webgriffe_sylius_akeneo.product.importer', Importer::class)
        ->args([
            service('sylius.factory.product_variant'),
            service('sylius.repository.product_variant'),
            service('sylius.repository.product'),
            service('webgriffe_sylius_akeneo.api_client'),
            service('webgriffe_sylius_akeneo.product.value_handlers_resolver'),
            service('sylius.factory.product'),
            service('webgriffe_sylius_akeneo.product.taxons_resolver'),
            service('webgriffe_sylius_akeneo.product.product_options_resolver'),
            service('event_dispatcher'),
            service('webgriffe_sylius_akeneo.product.channels_resolver'),
            service('webgriffe_sylius_akeneo.product.status_resolver'),
            service('sylius.factory.product_taxon'),
            service('webgriffe_sylius_akeneo.product.variant_status_resolver'),
            service('webgriffe_sylius_akeneo.product.validator'),
        ])
        ->tag('webgriffe_sylius_akeneo.importer')
        ->tag('webgriffe_sylius_akeneo.reconciler')
    ;

    $services->set('webgriffe_sylius_akeneo.product.validator', Validator::class)
        ->args([
            service('validator'),
            param('sylius.form.type.product.validation_groups'),
            param('sylius.form.type.product_variant.validation_groups'),
        ])
    ;
};
