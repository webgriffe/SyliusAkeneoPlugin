@importing_generic_items
Feature: Importing items
    In order to import data from Akeneo
    As an Administrator
    I want to import items from the Akeneo PIM

    Background:
        Given I am logged in as an administrator
        And the store has locale "en_US"
        And the store has a product association type "Pack" with a code "PACK"

    @cli @ui
    Scenario: Importing items when no item is modified since the given date
        When I import items for all importers modified since date "2020-01-20 01:00:00"
        And I browse products
        Then I should see 0 products in the list

    @cli @ui
    Scenario: Importing items without a since date
        When I import items for all importers with no since date
        Then I should be notified that a since date is required
        When I browse products
        Then I should see 0 products in the list

    @cli @ui
    Scenario: Importing items with an invalid since date
        When I import items for all importers with invalid since date
        Then I should be notified that the since date must be a valid date
        When I browse products
        Then I should see 0 products in the list

    @cli @ui
    Scenario: Importing items with a since date specified from a not existent file
        When I import items with since date specified from a not existent file
        Then I should be notified that the since date file does not exists
        When I browse products
        Then I should see 0 products in the list

    @cli @ui @javascript
    Scenario: Importing all items regardless last modified date
        Given there is a product "1314976" updated at "2022-06-15" on Akeneo
        And the product "1314976" has an attribute "name" with data "Product 1314976"
        And there is a product "10597353" updated at "2022-07-23" on Akeneo
        And the product "10597353" has an attribute "name" with data "Product 10597353"
        And there is a product "11164822" updated at "2022-08-01" on Akeneo
        And the product "11164822" has an attribute "name" with data "Product 11164822"
        And the product "11164822" has an association with product "10597353" for association with code "PACK"
        When I import all from Akeneo
        And I browse products
        Then I should see 3 products in the list
        And the product with code "11164822" should have an association "Pack" with product "10597353"

    @cli @ui @javascript
    Scenario: Importing all items for one importer only
        Given there is a product "1314976" updated at "2022-06-15" on Akeneo
        And there is a product "10597353" updated at "2022-07-23" on Akeneo
        And there is a product "11164822" updated at "2022-08-01" on Akeneo
        When I import all items for the "Product" importer
        And I browse products
        Then I should see 3 products in the list
        And the product with code "11164822" should not have an association "Pack" with product "10597353"

    @cli @ui
    Scenario: Importing all items for a not existent importer
        When I import all items for a not existent importer
        Then I should be notified that the importer does not exist
        When I browse products
        Then I should see 0 products in the list
