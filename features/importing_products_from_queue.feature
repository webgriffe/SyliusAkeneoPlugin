@importing_products
Feature: Importing products from queue
  In order to show updated data about my products
  As a Store Owner
  I want to import products from Akeneo PIM queue

  Scenario: Importing single product model from queue
    Given the store operates on a single channel
    And the store is also available in "it_IT"
    And there is one product model to import with identifier "MUG_SW" in the Akeneo queue
    When I import products from queue
    Then the product "MUG_SW" should exists with the right data
    And the queue item has been marked as imported
