@reconcile_products
Feature: Reconcile products
  In order to conciliate my products in the store with the products from Akeneo
  As a Store Owner
  I want to reconcile them

  Background:
    Given the store operates on a single channel in "United States"

  @cli
  Scenario: Reconcile simple products
    Given there is a product "PRODUCT_1" updated at "2021-04-19" on Akeneo
    And the store has a product "product-1"
    And the store has a product "product-2"
    When I reconcile items
    Then the "product-1" product should be enabled
    Then the "product-2" product should be disabled

  @cli
  Scenario: Reconcile configurable products
    Given there is a product "PRODUCT_1_VARIANT_1" updated at "2021-04-19" on Akeneo
    And there is a product "PRODUCT_1_VARIANT_2" updated at "2021-04-19" on Akeneo
    And there is a product "PRODUCT_2_VARIANT_2" updated at "2021-04-19" on Akeneo
    And the store has a product "product-1-variant-1"
    And this product has "product-1-variant-2" variant priced at "$25"
    And the store has a product "product-2-variant-1"
    And this product has "product-2-variant-2" variant priced at "$25"
    And the store has a product "product-3-variant-1"
    And this product has "product-3-variant-2" variant priced at "$25"
    When I reconcile items
    Then the "product-1-variant-1" product should be enabled
    And the "product-1-variant-1" product variant should be enabled
    And the "product-1-variant-2" product variant should be enabled
    And the "product-2-variant-1" product should be enabled
    And the "product-2-variant-1" product variant should be disabled
    And the "product-2-variant-2" product variant should be enabled
    And the "product-3-variant-1" product should be disabled
    And the "product-3-variant-1" product variant should be disabled
    And the "product-3-variant-2" product variant should be disabled
