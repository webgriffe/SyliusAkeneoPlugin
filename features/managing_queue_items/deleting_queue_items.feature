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