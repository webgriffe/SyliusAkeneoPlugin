@importing_products
Feature: Importing products from queue
  In order to show updated data about my products
  As a Store Owner
  I want to import products from Akeneo PIM queue

  @cli
  Scenario: Importing single product model and its variants from queue
    Given the store operates on a single channel
    And the store is also available in "it_IT"
    And there is one item to import with identifier "braided-hat-m" for the "Product" importer in the Akeneo queue
    And there is one item to import with identifier "braided-hat-l" for the "Product" importer in the Akeneo queue
    When I import all from Akeneo
    Then the product "model-braided-hat" should exists with the right data
    And the product variant "braided-hat-m" of product "model-braided-hat" should exists with the right data
    And the product variant "braided-hat-l" of product "model-braided-hat" should exists with the right data

  @cli
  Scenario: Keeping the queue item as not imported while importing non existent product model from queue
    Given the store operates on a single channel
    And the store is also available in "it_IT"
    And there is one item to import with identifier "NOT_EXISTS" for the "Product" importer in the Akeneo queue
    When I import all from Akeneo
    Then the item import message for "NOT_EXISTS" identifier and the "Product" importer should have failed
    And the product "NOT_EXISTS" should not exists

  @cli
  Scenario: Going on with subsequent product imports when any fail
    Given the store operates on a single channel
    And the store is also available in "it_IT"
    And there is one item to import with identifier "NOT_EXISTS" for the "Product" importer in the Akeneo queue
    And there is one item to import with identifier "braided-hat-m" for the "Product" importer in the Akeneo queue
    When I import all from Akeneo
    Then the item import message for "NOT_EXISTS" identifier and the "Product" importer should have failed
    And the product "NOT_EXISTS" should not exists
    And the product variant "braided-hat-m" of product "model-braided-hat" should exists with the right data

  @cli
  Scenario: Importing products with images should not leave temporary files in temporary files directory
    Given the store operates on a single channel
    And the store is also available in "it_IT"
    And there is one item to import with identifier "braided-hat-m" for the "Product" importer in the Akeneo queue
    And there is one item to import with identifier "braided-hat-l" for the "Product" importer in the Akeneo queue
    When I import all from Akeneo
    Then there should not be any temporary file in the temporary files directory
