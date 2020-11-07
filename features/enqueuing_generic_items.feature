@enqueuing_generic_items
Feature: Enqueuing items
  In order to import data from Akeneo
  As a Store Owner
  I want to add enqueue items to the Akeneo PIM queue

  @cli
  Scenario: Enqueueing items when no item is modified since the given date
    When I enqueue items for all importers modified since date "2020-01-20 01:00:00"
    Then there should be no item in the Akeneo queue

  @cli
  Scenario: Enqueueing items without a since date
    When I enqueue items for all importers with no since date
    Then I should be notified that a since date is required
    And there should be no item in the Akeneo queue

  @cli
  Scenario: Enqueueing items with an invalid since date
    When I enqueue items for all importers with invalid since date
    Then I should be notified that the since date must be a valid date

  @cli
  Scenario: Enqueueing items with a since date specified from a not existent file
    When I enqueue items with since date specified from a not existent file
    Then I should be notified that the since date file does not exists

  @cli
  Scenario: Avoiding to enqueue two times the same item if it has not been imported yet
    Given there is a product "product-1" updated at "2020-01-20 22:23:13" on Akeneo
    And there is one item to import with identifier "product-1" for the "Product" importer in the Akeneo queue
    When I enqueue items for all importers modified since date "2020-01-20 01:00:00"
    Then there should be only one queue item with identifier "product-1" for the "Product" importer in the Akeneo queue

  @cli
  Scenario: Enqueuing all items regardless last modified date
    Given there are 3 products on Akeneo
    When I enqueue all items for all importers
    Then there should be 3 items for the "Product" importer in the Akeneo queue
    And there should be 3 items for the "ProductAssociations" importer in the Akeneo queue

  @cli
  Scenario: Enqueuing all items for one importer only
    Given there are 3 products on Akeneo
    When I enqueue all items for the "Product" importer
    Then there should be 3 items for the "Product" importer in the Akeneo queue
    And there should be items for the "Product" importer only in the Akeneo queue
