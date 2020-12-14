@cleaning_queue
Feature: cleaning queue
  In order to have better performances during the akeneo pim import
  As a store owner
  I want to clean old already imported queue items.

  @cli
  Scenario: Cleaning the queue when there are no imported queue items
    Given there is a not imported item with identifier "braided-hat-m" for the "Product" importer in the Akeneo queue
    When I clean the queue
    Then I should be notified that there are no items to clean

  @cli
  Scenario: Cleaning the queue when there are some imported items to clean
    Given there is an already imported item with identifier "braided-hat-m" for the "Product" importer in the Akeneo queue
    And this item has been imported 15 days ago
    When I clean the queue
    Then I should be notified that 1 item has been deleted
    And there shouldn't be any more item to clean

  @cli
  Scenario: Cleaning the queue specifying the retention number of days
    Given there is an already imported item with identifier "braided-hat-m" for the "Product" importer in the Akeneo queue
    And this item has been imported 15 days ago
    And there is an already imported item with identifier "braided-hat-s" for the "Product" importer in the Akeneo queue
    And this item has been imported 20 days ago
    When I clean the queue specifying 16 days of retention
    Then I should be notified that 1 item has been deleted
    And there shouldn't be any more item to clean
