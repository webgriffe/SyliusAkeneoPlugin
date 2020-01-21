@importing_products
Feature: Importing products from queue
  In order to show updated data about my products
  As a Store Owner
  I want to import products from Akeneo PIM queue

  Scenario: Importing single product model and its variants from queue
    Given the store operates on a single channel
    And the store is also available in "it_IT"
    And there is one product to import with identifier "braided-hat-m" in the Akeneo queue
    And there is one product to import with identifier "Braided-hat-l" in the Akeneo queue
    When I import products from queue
    Then the product "model-braided-hat" should exists with the right data
    And the product variant "braided-hat-m" of product "model-braided-hat" should exists with the right data
    And the product variant "Braided-hat-l" of product "model-braided-hat" should exists with the right data

  Scenario: An error occured while importing non existent product model from queue
    Given the store operates on a single channel
    And the store is also available in "it_IT"
    And there is one product to import with identifier "NOT_EXISTS" in the Akeneo queue
    When I import products from queue
    Then the product "NOT_EXISTS" should not exists
    And the queue item has not been marked as imported
    And the queue item has an error message

  Scenario: Continue to import products when any fail
    Given the store operates on a single channel
    And the store is also available in "it_IT"
    And there is one product to import with identifier "NOT_EXISTS" in the Akeneo queue
    And there is one product to import with identifier "braided-hat-m" in the Akeneo queue
    When I import products from queue
    Then the product "NOT_EXISTS" should not exists
    And the product variant "braided-hat-m" of product "model-braided-hat" should exists with the right data
