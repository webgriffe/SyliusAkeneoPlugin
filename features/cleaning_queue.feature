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



