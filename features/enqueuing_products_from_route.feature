@enqueuing_products_from_route
Feature: Browsing products items
  In order to import the product from Akeneo
  As an Administrator
  I want to enqueue the product in Akeneo items queue

  Background:
    Given I am logged in as an administrator
    And there is a product item with identifier "braided-hat-m"
    And there is a product item with identifier "braided-hat-l"
    And there is one item to import with identifier "braided-hat-l" for the "Product" importer in the Akeneo queue

  @ui
  Scenario: Enqueue a product
    When I browse product item
    And And I schedule an Akeneo PIM import for the "braided-hat-m" product
    And I should be notified that it has been successfully enqueued
    Then I browse Akeneo queue items
    And I should see 2, not imported, queue items in the list

  @ui
  Scenario: Enqueue a product already enqueued
    When I browse product item
    And And I schedule an Akeneo PIM import for the "braided-hat-l" product
    And I should be notified that it has been already enqueued
    Then I browse Akeneo queue items
    And I should see 1, not imported, queue items in the list
