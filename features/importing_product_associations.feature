@importing_product_associations
Feature: Importing product associations from queue
    In order to show products associations for my products
    As a Store Owner
    I want to import product associations from Akeneo PIM queue

    @cli
    Scenario: Import product associations for already existent products
        Given the store operates on a single channel
        And the store has a product "10627329"
        And the store has a product "upsell-product-1" with code "upsell-product-1"
        And the store has a product "upsell-product-2" with code "upsell-product-2"
        And the store has a product association type "Upsell" with a code "UPSELL"
        And there is a product "10627329" on Akeneo
        And the product "10627329" has an association with product "upsell-product-1" for association with code "UPSELL"
        And the product "10627329" has an association with product "upsell-product-2" for association with code "UPSELL"
        When I import all "ProductAssociations" from Akeneo
        Then the product "10627329" should be associated to product "upsell-product-1" for association with code "UPSELL"
        And the product "10627329" should be associated to product "upsell-product-2" for association with code "UPSELL"
