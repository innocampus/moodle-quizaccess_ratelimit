@mod @quizaccess @quizaccess_ratelimit @javascript
Feature: Getting rate limited.
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
    And the following config values are set as admin:
      | ms_between_attempts | 20000 | quizaccess_ratelimit |
      # 20 seconds between quiz attempts are just for testing purposes of course.
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              |
      | Test questions   | truefalse | First question | Answer the first question |
    And the following "activity" exists:
      | activity                     | quiz           |
      | course                       | C1             |
      | idnumber                     | quiz1          |
      | name                         | Test quiz name |
      | timelimit                    | 3600           |
      # A preflight check form (e.g. due to a set time limit or password) is required for rate limiting to take effect.
    And quiz "Test quiz name" contains the following questions:
      | question       | page |
      | First question | 1    |

#  Scenario: Check settings.
#    When I log in as "admin"
#    And I navigate to "Plugins > Rate limiting access rule" in site administration
#    Then the following fields match these values:
#      | s_quizaccess_ratelimit_ms_between_attempts | 20000 |

  Scenario: Enforce wait time.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I wait until the page is ready
    And I press "Attempt quiz"
    And I press "Start attempt"
    And I should not see "The server is processing many requests at the moment."
    And I log out
    And I wait "2" seconds

    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I wait until the page is ready
    And I press "Attempt quiz"
    And I press "Start attempt"
    Then I should see "The server is processing many requests at the moment. Please wait until your quiz starts in a few seconds."
