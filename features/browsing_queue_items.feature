@browsing_queue_items
Feature: Browsing queue items
  In order to see the status of imported and not imported items from Akeneo
  As an Administrator
  I want to browse the Akeneo items queue

  Background:
    Given I am logged in as an administrator

  @ui
  Scenario: Browsing not imported items
    Given there is a not imported item with identifier "braided-hat-m" for the "Product" importer in the Akeneo queue
    And there is a not imported item with identifier "braided-hat-l" for the "Product" importer in the Akeneo queue
    And there is an already imported item with identifier "braided-hat-s" for the "Product" importer in the Akeneo queue
    When I browse Akeneo queue items
    Then I should see 2 queue items in the list
