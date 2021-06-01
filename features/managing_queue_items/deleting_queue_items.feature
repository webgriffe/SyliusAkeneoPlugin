@managing_queue_items
Feature: Deleting queue items
  In order to keep the Akeneo import queue clean
  As an Administrator
  I want to delete queue items that I know that will never be imported

  Background:
    Given I am logged in as an administrator

  @ui
  Scenario: Deleting single queue item
    Given there is a not imported item with identifier "braided-hat-m" for the "Product" importer in the Akeneo queue
    And I browse Akeneo queue items
    When I delete the "braided-hat-m" queue item
    Then I should be notified that it has been successfully deleted
    And this queue item should no longer exist in the queue

  @ui @javascript
  Scenario: Deleting multiple queue items at once
    Given there is a not imported item with identifier "braided-hat-l" for the "Product" importer in the Akeneo queue
    And there is a not imported item with identifier "braided-hat-m" for the "Product" importer in the Akeneo queue
    And there is a not imported item with identifier "braided-hat-s" for the "Product" importer in the Akeneo queue
    When I browse Akeneo queue items
    And I check the "braided-hat-m" queue item
    And I check also the "braided-hat-s" queue item
    And I delete them
    Then I should be notified that they have been successfully deleted
    And I should see a single queue item in the list
    And I should see the "braided-hat-l" queue item in the list
