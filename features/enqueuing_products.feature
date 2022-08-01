@enqueuing_products
Feature: Enqueuing products
  In order to import my products from Akeneo
  As a Store Owner
  I want to add them to the Akeneo PIM queue

  @cli
  Scenario: Enqueuing products modified since a given date
    Given there is a product "product-1" updated at "2020-01-10 22:23:13" on Akeneo
    And there is a product "product-2" updated at "2020-01-21 09:54:12" on Akeneo
    And there is a product "product-3" updated at "2020-01-22 08:15:08" on Akeneo
    When I import items for all importers modified since date "2020-01-20 01:00:00"
    Then the queue item with identifier "product-1" for the "Product" importer should not be in the Akeneo queue
    And the queue item with identifier "product-2" for the "Product" importer should be in the Akeneo queue
    And the queue item with identifier "product-3" for the "Product" importer should be in the Akeneo queue

  @cli
  Scenario: There are no products modified since datetime read in file
    Given there is a file with name "last-date" and content "2020-01-20 01:00:00"
    And current date time is "2020-01-25T12:00:00+01:00"
    When I import items for all importers modified since date specified from file "last-date"
    Then there should be no item in the queue for the "Product" importer
    And there is a file with name "last-date" that contains "2020-01-25T12:00:00+01:00"

  @cli
  Scenario: Enqueuing products modified since datetime read in file
    Given there is a product "product-1" updated at "2020-01-10 22:23:13" on Akeneo
    And there is a product "product-2" updated at "2020-01-21 09:54:12" on Akeneo
    And there is a file with name "last-date" and content "2020-01-20 01:00:00"
    And current date time is "2020-01-25T12:00:00+01:00"
    When I import items for all importers modified since date specified from file "last-date"
    Then the queue item with identifier "product-1" for the "Product" importer should not be in the Akeneo queue
    And the queue item with identifier "product-2" for the "Product" importer should be in the Akeneo queue
    And there is a file with name "last-date" that contains "2020-01-25T12:00:00+01:00"

  @ui
  Scenario: Enqueuing a simple product
    Given I am logged in as an administrator
    And the store has a product "Braided hat m"
    When I browse products
    And I schedule an Akeneo PIM import for the "Braided hat m" product
    Then I should be notified that it has been successfully enqueued

  @ui
  Scenario: Enqueuing a product already enqueued
    Given I am logged in as an administrator
    And the store has a product "Braided hat l"
    And there is one item to import with identifier "BRAIDED_HAT_L" for the "Product" importer in the Akeneo queue
    When I browse products
    And I schedule an Akeneo PIM import for the "Braided hat l" product
    Then I should be notified that it has been successfully enqueued

  @ui
  Scenario: Enqueuing a configurable product
    Given the store operates on a single channel
    And the store has a "Braided hat" configurable product
    And this product has "Small", "Medium" and "Large" variants
    And I am logged in as an administrator
    When I browse products
    And I schedule an Akeneo PIM import for the "Braided hat" product
    Then I should be notified that it has been successfully enqueued
