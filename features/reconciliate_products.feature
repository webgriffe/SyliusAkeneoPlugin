@reconcile_products
Feature: Reconcile products
  In order to conciliate my products in the store with the products from Akeneo
  As a Store Owner
  I want to reconcile them

  Background:
    Given the store operates on a single channel in "United States"

  @cli
  Scenario: Reconcile simple products
    Given there is a product "product-1" updated at "2021-01-01" on Akeneo
    And the store has a product "product-1"
    And the store has a product "product-2"
    When I reconcile items
    Then the "product-1" product is enabled
    And the "product-2" product is disabled

  @cli
  Scenario: Reconcile configurable products
    Given there is a product "product-1-variant-1" updated at "2021-04-19" on Akeneo
    And there is a product "product-1-variant-2" updated at "2021-04-19" on Akeneo
    And there is a product "product-2-variant-2" updated at "2021-04-19" on Akeneo
    And the store has a product "product-1-variant-1"
    And this product has "product-1-variant-2" variant priced at "$25"
    And the store has a product "product-2-variant-1"
    And this product has "product-2-variant-2" variant priced at "$25"
    And the store has a product "product-3-variant-1"
    And this product has "product-3-variant-2" variant priced at "$25"
    When I reconcile items
    Then the "product-1-variant-1" product variant is enabled
    Then the "product-1-variant-2" product variant is enabled
    And the "product-2-variant-1" product variant is disabled
    And the "product-2-variant-2" product variant is enabled
    And the "product-3-variant-1" product variant is disabled
    And the "product-3-variant-2" product variant is disabled
