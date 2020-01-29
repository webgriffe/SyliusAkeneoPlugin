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

  Scenario: Keeping the queue item as not imported while importing non existent product model from queue
    Given the store operates on a single channel
    And the store is also available in "it_IT"
    And there is one product to import with identifier "NOT_EXISTS" in the Akeneo queue
    When I import products from queue
    Then the product "NOT_EXISTS" should not exists
    And the queue item for product with identifier "NOT_EXISTS" has not been marked as imported
    And the queue item for product with identifier "NOT_EXISTS" has an error message

  Scenario: Going on with subsequent product imports when any fail
    Given the store operates on a single channel
    And the store is also available in "it_IT"
    And there is one product to import with identifier "NOT_EXISTS" in the Akeneo queue
    And there is one product to import with identifier "braided-hat-m" in the Akeneo queue
    When I import products from queue
    Then the product "NOT_EXISTS" should not exists
    And the product variant "braided-hat-m" of product "model-braided-hat" should exists with the right data
    And the queue item for product with identifier "braided-hat-m" has been marked as imported

  # todo: this scenario seems to cover too many cases, it should be split in multiple scenarios
  @todo
  Scenario: Importing product with missing mandatory data should not mark them as imported and going on importing subsequent products
    Given the store operates on a single channel
    And the store is also available in "it_IT"
    And there is one product to import with identifier "no-name-product" in the Akeneo queue
    And there is one product to import with identifier "braided-hat-m" in the Akeneo queue
    When I import products from queue
    Then there should be only one product queue item for "no-name-product" in the Akeneo queue
    And there should be only one product queue item for "braided-hat-m" in the Akeneo queue
    And the queue item for product with identifier "no-name-product" has not been marked as imported
    And the queue item for product with identifier "no-name-product" has an error message containing "NOT NULL constraint failed: sylius_product_translation.name"
    And the queue item for product with identifier "braided-hat-m" has been marked as imported
    And the product "no-name-product" should not exists
    And the product variant "braided-hat-m" of product "model-braided-hat" should exists with the right data
