@reconciliate_products
Feature: Reconciliation products
  In order to conciliate my products in the store with the products from Akeneo
  As a Store Owner
  I want to reconciliate them

  @cli
  Scenario: Reconciliate products
    Given there are 2 products on Akeneo
    When I reconciliate items
