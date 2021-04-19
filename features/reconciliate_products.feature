@reconciliate_products
Feature: Reconciliation products
  In order to conciliate my products in the store with the products from Akeneo
  As a Store Owner
  I want to reconciliate them

  @cli
  Scenario: Reconciliate simple products
    Given there is 1 product on Akeneo
    And the store has a product "product-1"
    And the store has a product "product-2"
    When I reconciliate items
    Then the "product-1" product is enabled
    And the "product-2" product is disabled
