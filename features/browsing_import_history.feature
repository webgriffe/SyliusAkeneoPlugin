@browsing_import_history
Feature: Browsing import history
    In order to check the reasons of an import failure
    As an Administrator
    I want to browse the import from Akeneo results history

    Background:
        Given I am logged in as an administrator
        And there is a successful import result for an item with identifier "braided-hat-m" for the "Product" entity
        And there is a failed import result for an item with identifier "braided-hat-l" for the "Product" entity
        And there is a successful import result for an item with identifier "braided-hat-m" for the "ProductAssociations" entity
        And there is a failed import result for an item with identifier "braided-hat-l" for the "ProductAssociations" entity
        When I browse the import from Akeneo results history

    @ui
    Scenario: Browsing all items
        Then I should see 4 import result items in the list

    @ui
    Scenario: Browsing failed items
        When I choose "No" as a successful filter
        And I filter
        Then I should see 2 import result items in the list

    @ui
    Scenario: Filtering items by entity
        When I specify "Associations" as an entity filter
        And I filter
        Then I should see 2 import result items in the list

    @ui
    Scenario: Filtering items by identifier
        When I specify "hat-l" as an identifier filter
        And I filter
        Then I should see 2 import result items in the list
