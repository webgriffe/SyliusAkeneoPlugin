@enqueuing_products_associations
Feature: Enqueuing products associations
  In order to import my products associations from Akeneo
  As a Store Owner
  I want to add them to the Akeneo PIM queue

  @cli
  Scenario: Enqueuing products associations for products modified since a given date
    Given there is a product "product-1" updated at "2020-01-10 22:23:13" on Akeneo
    And there is a product "product-2" updated at "2020-01-21 09:54:12" on Akeneo
    And there is a product "product-3" updated at "2020-01-22 08:15:08" on Akeneo
    When I import items for all importers modified since date "2020-01-20 01:00:00"
    Then the queue item with identifier "product-1" for the "ProductAssociations" importer should not be in the Akeneo queue
    And the queue item with identifier "product-2" for the "ProductAssociations" importer should be in the Akeneo queue
    And the queue item with identifier "product-3" for the "ProductAssociations" importer should be in the Akeneo queue
