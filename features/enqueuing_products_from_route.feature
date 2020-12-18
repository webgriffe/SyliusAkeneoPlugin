@enqueuing_products_from_route
Feature: Browsing products items
  In order to import the product from Akeneo
  As an Administrator
  I want to enqueue the product in Akeneo items queue

  Background:
    Given I am logged in as an administrator
    And there is a product item with identifier "braided-hat-m"
    And there is a product item with identifier "t-shirt-xl"
    And there is a product item with identifier "braided-hat-l"

  @ui
  Scenario: Browsing all items
    When I browse product item
    Then I should see 3 products in the list

  @ui
  Scenario: Enqueue two products
    When I browse product item
    And I click "Schedule Akeneo PIM import" button on "braided-hat-m" product
    And I click "Schedule Akeneo PIM import" button on "t-shirt-xl" product
    Then I browse Akeneo queue items
    And I should see 2, not imported, queue items in the list

  @ui
  Scenario: Enqueue two products already enqueued
    When I browse product item
    And I click "Schedule Akeneo PIM import" button on "braided-hat-m" product
    And I click "Schedule Akeneo PIM import" button on "t-shirt-xl" product
    Then I browse Akeneo queue items
    And I should see 2, not imported, queue items in the list
