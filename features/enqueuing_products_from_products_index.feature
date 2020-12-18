@enqueuing_products_from_products_index
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
  Scenario: Enqueue the product "braided-hat-m"
    When I browse product item
    And I click "Schedule Akeneo PIM import" button on "braided-hat-m" product
#    Then I should see "Akeneo PIM product import has been successfully scheduled"
    Then I browse Akeneo queue items
    And I should see 1, not imported, queue items in the list

  @ui
  Scenario: Enqueue the product "braided-hat-m" already imported
    When I browse product item
    And I click "Schedule Akeneo PIM import" button on "braided-hat-m" product
#    Then I should see "Akeneo PIM import for this product has been already scheduled before"
    Then I browse Akeneo queue items
    And I should see 1, not imported, queue items in the list

  @ui
  Scenario: Enqueue the product "t-shirt-xl"
    When I browse product item
    And I click "Schedule Akeneo PIM import" button on "braided-hat-m" product
    And I click "Schedule Akeneo PIM import" button on "t-shirt-xl" product
    Then I browse Akeneo queue items
    And I should see 2, not imported, queue items in the list

  @ui
  Scenario: Enqueue the product "t-shirt-xl" already imported
    When I browse product item
    And I click "Schedule Akeneo PIM import" button on "braided-hat-m" product
    And I click "Schedule Akeneo PIM import" button on "t-shirt-xl" product
    Then I browse Akeneo queue items
    And I should see 2, not imported, queue items in the list
