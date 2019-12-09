@importing_products
Feature: Importing products from queue
  In order to show updated data about my products
  As a Store Owner
  I want to import products from Akeneo PIM queue

  Scenario: Importing single product model from queue
    Given the store operates on a single channel
    And there is one product model to import with identifier "model-braided-hat" in the Akeneo queue
    When I import products from queue
    Then the product "model-braided-hat" should exists with the right data
