@enqueuing
Feature: Enqueuing items
  In order to import data from Akeneo
  As a Store Owner
  I want to add enqueue items to the Akeneo PIM queue

  Scenario: There is nothing modified since a given date
    When I run enqueue command with since date "2020-01-20 01:00:00"
    Then the command should have run successfully
    And there should be no item in the Akeneo queue

  Scenario: The command cannot be run without since parameter
    When I run enqueue command with no since date
    Then the command should have thrown exception with message containing 'One of "--since" and "--since-file" paramaters must be specified'
    And there should be no item in the Akeneo queue

  Scenario: The command cannot be run with bad since date
    When I run enqueue command with since date "bad date"
    Then the command should have thrown exception with message containing 'The "since" argument must be a valid date'
    And there should be no item in the Akeneo queue

  Scenario: Run the command with not existent since file
    When I run enqueue command with since file "last-date"
    Then the command should have thrown exception with message containing 'does not exists'
    And there should be no item in the Akeneo queue
    And there is no file with name "last-date"
