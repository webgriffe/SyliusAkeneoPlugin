@enqueuing
Feature: Enqueuing products
  In order to import my products from Akeneo
  As a Store Owner
  I want to add them to the Akeneo PIM queue

  Scenario: Enqueuing products modified since a given date
    Given there is a product "product-1" updated at "2020-01-10 22:23:13" on Akeneo
    And there is a product "product-2" updated at "2020-01-21 09:54:12" on Akeneo
    And there is a product "product-3" updated at "2020-01-22 08:15:08" on Akeneo
    When I run enqueue command with since date "2020-01-20 01:00:00"
    Then the command should have run successfully
    And the product "product-1" should not be in the Akeneo queue
    And the product "product-2" should be in the Akeneo queue
    And the product "product-3" should be in the Akeneo queue

  Scenario: There are no products modified since datetime read in file
    Given there is a file with name "last-date" and content "2020-01-20 01:00:00"
    And current date time is "2020-01-25 12:00:00"
    When I run enqueue command with since file "last-date"
    Then the command should have run successfully
    And there should be no product in the Akeneo queue
    And there is a file with name "last-date" that contains "2020-01-25T12:00:00+01:00"

  Scenario: Enqueuing products modified since datetime read in file
    Given there is a product "product-1" updated at "2020-01-10 22:23:13" on Akeneo
    And there is a product "product-2" updated at "2020-01-21 09:54:12" on Akeneo
    And there is a file with name "last-date" and content "2020-01-20 01:00:00"
    And current date time is "2020-01-25 12:00:00"
    When I run enqueue command with since file "last-date"
    Then the command should have run successfully
    And the product "product-1" should not be in the Akeneo queue
    And the product "product-2" should be in the Akeneo queue
    And there is a file with name "last-date" that contains "2020-01-25T12:00:00+01:00"

  # todo: this should be generalized and moved into enqueuing.feature
  Scenario: Avoiding to enqueue two times the same product if it has not been imported yet
    Given there is a product "product-1" updated at "2020-01-20 22:23:13" on Akeneo
    And there is one product to import with identifier "product-1" in the Akeneo queue
    When I run enqueue command with since date "2020-01-20 01:00:00"
    Then there should be only one product queue item for "product-1" in the Akeneo queue
