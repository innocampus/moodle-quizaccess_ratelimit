@mod @quizaccess @quizaccess_ratelimit @javascript
Feature: Getting rate limited in different scenarios.
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student111   | 1        | student1@example.com |
      | student2 | Student222   | 2        | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
    And the following config values are set as admin:
      | ms_between_attempts | 22000 | quizaccess_ratelimit |
      # 20 seconds between quiz attempts are just for testing purposes of course.
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              |
      | Test questions   | truefalse | First question | Answer the first question |

  Scenario: Secure window enabled.
    Given the following "activity" exists:
      | activity                     | quiz           |
      | course                       | C1             |
      | idnumber                     | quiz1          |
      | name                         | Test quiz name |
      | timelimit                    | 3600           |
      | browsersecurity              | securewindow   |
      # A preflight check form (e.g. due to a set time limit or password) is required for rate limiting to take effect.
    And quiz "Test quiz name" contains the following questions:
      | question       | page |
      | First question | 1    |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I wait until the page is ready
    And I press "Attempt quiz"
    And I press "Start attempt"
    And I wait "1" seconds
    And I switch to a second window
    And I wait until the page is ready
    And I should see "Answer the first question"
    And I switch to a second window
    And I close all opened windows
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I wait until the page is ready
    And I press "Attempt quiz"
    And I press "Start attempt"
    And I should see "The server is processing many requests at the moment."
    And I wait "20" seconds
    And I switch to a second window
    Then I should see "Answer the first question"
    And I log out

  Scenario: Secure window cancel.
    Given the following "activity" exists:
      | activity                     | quiz           |
      | course                       | C1             |
      | idnumber                     | quiz1          |
      | name                         | Test quiz name |
      | timelimit                    | 3600           |
      | browsersecurity              | securewindow   |
      # A preflight check form (e.g. due to a set time limit or password) is required for rate limiting to take effect.
    And quiz "Test quiz name" contains the following questions:
      | question       | page |
      | First question | 1    |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I press "Attempt quiz"
    And I press "Start attempt"
    And I wait "1" seconds
    And I switch to a second window
    And I wait until the page is ready
    And I should see "Answer the first question"
    And I switch to a second window
    And I close all opened windows
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I wait until the page is ready
    And I press "Attempt quiz"
    And I press "Start attempt"
    And I should see "The server is processing many requests at the moment."
    And I wait "2" seconds
    And I click on ".close" "css" in the ".modal-content" "css_element"
    And I click on ".btn-cancel" "css" in the ".mform" "css_element"
    And I press "Attempt quiz"
    And I wait "5" seconds
    And I press "Start attempt"
    And I wait "15" seconds
    And I switch to a second window
    Then I should see "Answer the first question"
    And I log out

  Scenario: Secure window and password enabled.
    Given the following "activity" exists:
      | activity                     | quiz           |
      | course                       | C1             |
      | idnumber                     | quiz1          |
      | name                         | Test quiz name |
      | timelimit                    | 3600           |
      | browsersecurity              | securewindow   |
      | quizpassword                 | abcde          |
      # A preflight check form (e.g. due to a set time limit or password) is required for rate limiting to take effect.
    And quiz "Test quiz name" contains the following questions:
      | question       | page |
      | First question | 1    |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I press "Attempt quiz"
    Then I should see "To attempt this quiz you need to know the quiz password" in the "Start attempt" "dialogue"
    And I set the field "Quiz password" to "abcde"
    And I press "Start attempt"
    And I wait "1" seconds
    And I switch to a second window
    And I wait until the page is ready
    And I should see "Answer the first question"
    And I switch to a second window
    And I close all opened windows
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I wait until the page is ready
    And I press "Attempt quiz"
    Then I should see "To attempt this quiz you need to know the quiz password" in the "Start attempt" "dialogue"
    And I set the field "Quiz password" to "abcde"
    And I press "Start attempt"
    And I should see "The server is processing many requests at the moment."
    And I wait "20" seconds
    And I switch to a second window
    Then I should see "Answer the first question"
    And I log out

  Scenario: Enforce wait time.
    Given the following "activity" exists:
      | activity                     | quiz           |
      | course                       | C1             |
      | idnumber                     | quiz1          |
      | name                         | Test quiz name |
      | timelimit                    | 3600           |
      # A preflight check form (e.g. due to a set time limit or password) is required for rate limiting to take effect.
    And quiz "Test quiz name" contains the following questions:
      | question       | page |
      | First question | 1    |
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
    And I log out
