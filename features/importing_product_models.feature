@importing_product_models
Feature: Importing product models
    In order to show updated data about my products
    As a Store Owner
    I want to import product models from Akeneo PIM

    Background:
        Given the store operates on a single channel

    @cli
    Scenario: Importing product model and its variants
        Given there is an attribute "size" on Akeneo of type "pim_catalog_simpleselect"

        And there is a family variant "accessories_size" on Akeneo for the family "accessories"
        And the family variant "accessories_size" of family "accessories" has the attribute "size" as axes of first level

        And there is a product model "MODEL_BRAIDED_HAT" on Akeneo of family "accessories" having variant "accessories_size"

        And there is a product "BRAIDED_HAT_M" on Akeneo
        And the product "BRAIDED_HAT_M" has parent "MODEL_BRAIDED_HAT"
        And the product "BRAIDED_HAT_M" has a price attribute with amount "33.99" and currency "USD"

        And there is a product "BRAIDED_HAT_L" on Akeneo
        And the product "BRAIDED_HAT_L" has parent "MODEL_BRAIDED_HAT"
        And the product "BRAIDED_HAT_L" has a price attribute with amount "33.00" and currency "USD"

        And the store is also available in "it_IT"

        When I import all "ProductModels" from Akeneo
        Then the product "MODEL_BRAIDED_HAT" should exist
        And the product variant "BRAIDED_HAT_M" of product "MODEL_BRAIDED_HAT" should exist
        And the product variant "BRAIDED_HAT_L" of product "MODEL_BRAIDED_HAT" should exist
