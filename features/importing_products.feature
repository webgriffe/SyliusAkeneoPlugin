@importing_products
Feature: Importing products
    In order to show updated data about my products
    As a Store Owner
    I want to import products from Akeneo PIM

    Background:
        Given the store operates on a single channel

    @cli
    Scenario: Importing single product model and its variants
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

        When I import all from Akeneo
        Then the product "MODEL_BRAIDED_HAT" should exist
        And the product variant "BRAIDED_HAT_M" of product "MODEL_BRAIDED_HAT" should exist
        And the product variant "BRAIDED_HAT_L" of product "MODEL_BRAIDED_HAT" should exist

    @cli
    Scenario: Importing products with images should not leave temporary files in temporary files directory
        Given there is an attribute "size" on Akeneo of type "pim_catalog_simpleselect"
        Given there is an attribute "attachment" on Akeneo of type "pim_catalog_file"

        And there is a family variant "accessories_size" on Akeneo for the family "accessories"
        And the family variant "accessories_size" of family "accessories" has the attribute "size" as axes of first level

        And there is a product model "MODEL_BRAIDED_HAT" on Akeneo of family "accessories" having variant "accessories_size"

        And there is a product "BRAIDED_HAT_M" on Akeneo
        And the product "BRAIDED_HAT_M" has parent "MODEL_BRAIDED_HAT"
        And the product "BRAIDED_HAT_M" has a price attribute with amount "33.99" and currency "USD"
        And the product "BRAIDED_HAT_M" has an attribute "attachment" with data "sample.pdf"
        And the product "BRAIDED_HAT_M" has an attribute "image" with data "star_wars_m.jpeg"

        And there is a product "BRAIDED_HAT_L" on Akeneo
        And the product "BRAIDED_HAT_L" has parent "MODEL_BRAIDED_HAT"
        And the product "BRAIDED_HAT_L" has a price attribute with amount "33.00" and currency "USD"
        And the product "BRAIDED_HAT_L" has an attribute "attachment" with data "sample.pdf"
        And the product "BRAIDED_HAT_L" has an attribute "image" with data "star_wars_l.jpeg"

        And the store is also available in "it_IT"
        When I import all from Akeneo
        Then there should not be any temporary file in the temporary files directory

    @ui
    Scenario: Importing a simple product
        Given there is a product "11164822" on Akeneo
        And I am logged in as an administrator
        And the store has a product "11164822"
        When I browse products
        And I schedule an Akeneo PIM import for the "11164822" product
        Then I should be notified that "11164822" has been successfully enqueued
        And the product "11164822" should exist
        And the product variant "11164822" of product "11164822" should exist

    @ui
    Scenario: Importing a configurable product already existing in the store
        Given there is a product "BRAIDED_HAT_M" on Akeneo
        And the product "BRAIDED_HAT_M" has parent "MODEL_BRAIDED_HAT"

        And there is a product "BRAIDED_HAT_L" on Akeneo
        And the product "BRAIDED_HAT_L" has parent "MODEL_BRAIDED_HAT"

        And there is a product "BRAIDED_HAT_S" on Akeneo
        And the product "BRAIDED_HAT_S" has parent "MODEL_BRAIDED_HAT"

        And the store has a "Model Braided Hat" configurable product
        And this product has "Braided Hat S", "Braided Hat M" and "Braided Hat L" variants
        And I am logged in as an administrator
        When I browse products
        And I schedule an Akeneo PIM import for the "Model Braided Hat" product
        Then I should be notified that "BRAIDED_HAT_S" has been successfully enqueued
        And I should be notified that "BRAIDED_HAT_M" has been successfully enqueued
        And I should be notified that "BRAIDED_HAT_L" has been successfully enqueued
        And the product "MODEL_BRAIDED_HAT" should exist
        And the product variant "BRAIDED_HAT_S" of product "MODEL_BRAIDED_HAT" should exist
        And the product variant "BRAIDED_HAT_M" of product "MODEL_BRAIDED_HAT" should exist
        And the product variant "BRAIDED_HAT_L" of product "MODEL_BRAIDED_HAT" should exist

    @cli
    Scenario: Preventing database inconsistency errors that will block product imports
        Given there is a product "EMPTY_NAME_PRODUCT" on Akeneo
        And the product "EMPTY_NAME_PRODUCT" has an attribute "description" with data "No name product"
        When I try to import all from Akeneo
        Then I should get an error about product name cannot be empty
        And this error should also be logged in the database
