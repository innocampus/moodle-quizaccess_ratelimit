@mod @quizaccess @quizaccess_ratelimit @javascript
Feature: Quiz getting rate limited
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
      | ms_between_attempts | 25000 | quizaccess_ratelimit |
      # 20 seconds between quiz attempts are just for testing purposes of course.
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              |
      | Test questions   | truefalse | First question | Answer the first question |
    And I reset the quiz rate limit counters

  # Info:
  # A preflight check form is required for rate limiting to take effect.
  # For this reason, in every following scenario the test quiz either has a time limit set or is password protected.

  Scenario: Getting rate limited in quiz with time limit and secure window enabled
    Given the following "activity" exists:
      | activity                     | quiz           |
      | course                       | C1             |
      | idnumber                     | quiz1          |
      | name                         | Test quiz name |
      | timelimit                    | 3600           |
      | browsersecurity              | securewindow   |
    And quiz "Test quiz name" contains the following questions:
      | question       | page |
      | First question | 1    |
    # Start a quiz attempt as "student1".
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I wait until the page is ready
    And I press "Attempt quiz"
    And I press "Start attempt"
    # New window should pop up.
    Then I switch to a second window
    And I wait until the page is ready
    # Attempt should start immediately for "student1".
    Then I press "Start attempt"
    And I should see "Answer the first question"
    And I close all opened windows
    And I log out
    # Try to start a concurrent quiz attempt as "student2".
    Then I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I wait until the page is ready
    And I press "Attempt quiz"
    And I press "Start attempt"
    # New window should pop up.
    Then I switch to a second window
    And I wait until the page is ready
    # Rate limiting should kick in and prevent "student2" from starting the attempt.
    Then I press "Start attempt"
    And I should not see "Answer the first question"
    And I should see "The server is processing many requests at the moment. Please wait until your quiz starts in a few seconds."
    # Waiting 20 seconds should be enough. The attempt should have started by then.
    Then I wait "20" seconds
    And I should see "Answer the first question"
    And I log out

  Scenario: Cancelling secure window in a rate limited quiz with time limit
    Given the following "activity" exists:
      | activity                     | quiz           |
      | course                       | C1             |
      | idnumber                     | quiz1          |
      | name                         | Test quiz name |
      | timelimit                    | 3600           |
      | browsersecurity              | securewindow   |
    And quiz "Test quiz name" contains the following questions:
      | question       | page |
      | First question | 1    |
    # Start a quiz attempt as "student1".
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I press "Attempt quiz"
    And I press "Start attempt"
    # New window should pop up.
    Then I switch to a second window
    And I wait until the page is ready
    # Attempt should start immediately for "student1".
    Then I press "Start attempt"
    And I should see "Answer the first question"
    And I close all opened windows
    And I log out
    # Try to start a concurrent quiz attempt as "student2".
    Then I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I wait until the page is ready
    And I press "Attempt quiz"
    And I press "Start attempt"
    # New window should pop up.
    Then I switch to a second window
    And I wait until the page is ready
    # Rate limiting should kick in and prevent "student2" from starting the attempt.
    Then I press "Start attempt"
    And I should not see "Answer the first question"
    And I should see "The server is processing many requests at the moment. Please wait until your quiz starts in a few seconds."
    And I click on ".btn-close" "css" in the ".modal-content" "css_element"
    # TODO: The next 3 steps are a workaround because just using `And I press "Cancel"` causes an error in chromium -> `no such window: target window already closed`
    And I switch to the main window
    And I close all opened windows
    And I reload the page
    And I press "Attempt quiz"
    And I press "Start attempt"
    # New window should pop up.
    Then I switch to a second window
    And I wait until the page is ready
    # Rate limiting should still kick in and prevent "student2" from starting the attempt.
    Then I press "Start attempt"
    And I should not see "Answer the first question"
    And I should see "The server is processing many requests at the moment. Please wait until your quiz starts in a few seconds."
    # Waiting 20 seconds should be enough. The attempt should have started by then.
    Then I wait "20" seconds
    And I should see "Answer the first question"
    And I log out

  Scenario: Getting rate limited in password protected quiz with secure window enabled
    Given the following "activity" exists:
      | activity                     | quiz           |
      | course                       | C1             |
      | idnumber                     | quiz1          |
      | name                         | Test quiz name |
      | browsersecurity              | securewindow   |
      | quizpassword                 | abcde          |
    And quiz "Test quiz name" contains the following questions:
      | question       | page |
      | First question | 1    |
    # Start a quiz attempt as "student1".
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I press "Attempt quiz"
    And I should see "To attempt this quiz you need to know the quiz password" in the "Start attempt" "dialogue"
    And I set the field "Quiz password" to "abcde"
    And I press "Start attempt"
    # New window should pop up.
    Then I switch to a second window
    And I wait until the page is ready
    And I should see "To attempt this quiz you need to know the quiz password"
    And I set the field "Quiz password" to "abcde"
    # Attempt should start immediately for "student1".
    Then I press "Start attempt"
    And I should see "Answer the first question"
    And I close all opened windows
    And I log out
    # Try to start a concurrent quiz attempt as "student2".
    Then I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I wait until the page is ready
    And I press "Attempt quiz"
    Then I should see "To attempt this quiz you need to know the quiz password" in the "Start attempt" "dialogue"
    And I set the field "Quiz password" to "abcde"
    And I press "Start attempt"
    # New window should pop up.
    Then I switch to a second window
    And I wait until the page is ready
    Then I should see "To attempt this quiz you need to know the quiz password"
    And I set the field "Quiz password" to "abcde"
    # Rate limiting should kick in and prevent "student2" from starting the attempt.
    Then I press "Start attempt"
    And I should not see "Answer the first question"
    And I should see "The server is processing many requests at the moment. Please wait until your quiz starts in a few seconds."
    # Waiting 20 seconds should be enough. The attempt should have started by then.
    Then I wait "20" seconds
    And I should see "Answer the first question"
    And I log out

  Scenario: Getting rate limited in quiz with time limit
    Given the following "activity" exists:
      | activity                     | quiz           |
      | course                       | C1             |
      | idnumber                     | quiz1          |
      | name                         | Test quiz name |
      | timelimit                    | 3600           |
    And quiz "Test quiz name" contains the following questions:
      | question       | page |
      | First question | 1    |
    # Start a quiz attempt as "student1".
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I wait until the page is ready
    And I press "Attempt quiz"
    # Attempt should start immediately for "student1".
    Then I press "Start attempt"
    And I should see "Answer the first question"
    And I log out
    # Try to start a concurrent quiz attempt as "student2".
    Then I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I wait until the page is ready
    And I press "Attempt quiz"
    # Rate limiting should kick in and prevent "student2" from starting the attempt.
    Then I press "Start attempt"
    And I should not see "Answer the first question"
    And I should see "The server is processing many requests at the moment. Please wait until your quiz starts in a few seconds."
    # Waiting 20 seconds should be enough. The attempt should have started by then.
    Then I wait "20" seconds
    And I should see "Answer the first question"
    And I log out
