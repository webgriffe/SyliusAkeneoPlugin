@enqueuing_products_from_products_index
Feature: Browsing products items
  In order to import the product from Akeneo
  As an Administrator
  I want to enqueue the product in Akeneo items queue

  Background:
    Given I am logged in as an administrator
    And there is a product item with identifier "braided-hat-m"
    And there is a product item with identifier "braided-hat-l"

  @ui
  Scenario: Browsing all items
    When I browse product item
    Then I should see 2 products in the list
