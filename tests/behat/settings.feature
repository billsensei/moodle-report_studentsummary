# This file is part of Moodle - https://moodle.org/
#
# Moodle is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Moodle is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

@report @report_studentsummary
Feature: Student summary administration settings
  In order to tune the report
  As an administrator
  I need to configure how the report is built

  Scenario: The settings are available under Reports
    Given I log in as "admin"
    When I navigate to "Plugins > Reports > Student summary" in site administration
    Then I should see "Idle timeout"
    And I should see "Tail time"
    And I should see "Maximum courses listed"
    And I should see "Show time spent"

  @javascript
  Scenario: The Plugins overview page links to the settings
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    And I follow "Settings" in the "Student summary" "table_row"
    Then I should see "Idle timeout"
    And I should see "Maximum courses listed"

  Scenario: Time spent can be hidden by the administrator
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course A | CA        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | CA     | student |
    And the following config values are set as admin:
      | showtime | 0 | report_studentsummary |
    When I am on the "student1" "user > profile" page logged in as student1
    And I follow "Student summary"
    Then I should see "Time spent display is disabled by the administrator."

  Scenario: The course list is capped by the maximum courses setting
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course A | CA        |
      | Course B | CB        |
      | Course C | CC        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | CA     | student |
      | student1 | CB     | student |
      | student1 | CC     | student |
    And the following config values are set as admin:
      | maxcourses | 2 | report_studentsummary |
    When I am on the "student1" "user > profile" page logged in as student1
    And I follow "Student summary"
    Then I should see "+1 more courses not shown"

  Scenario: An unreasonable maximum courses value is rejected
    Given I log in as "admin"
    And I navigate to "Plugins > Reports > Student summary" in site administration
    When I set the field "Maximum courses listed" to "500"
    And I press "Save changes"
    Then I should see "Enter a whole number from 1 to 50."

  Scenario: The chosen palette and a box override are applied to the report
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
    And the following config values are set as admin:
      | palette      | highcontrast | report_studentsummary |
      | colour_grade | #e0f2fe      | report_studentsummary |
    When I am on the "student1" "user > profile" page logged in as student1
    And I follow "Student summary"
    Then "//div[contains(@class, 'report-studentsummary')][contains(@style, '--ss-ink:#000000')]" "xpath_element" should exist
    And "//div[contains(@class, 'report-studentsummary')][contains(@style, '--ss-amber-bg:#e0f2fe')]" "xpath_element" should exist
    And "//div[contains(@class, 'report-studentsummary')][contains(@style, '--ss-blue-border:#0b3d91')]" "xpath_element" should exist

  Scenario: A box colour that would make the text hard to read is rejected
    Given I log in as "admin"
    And I navigate to "Plugins > Reports > Student summary" in site administration
    When I set the field "Average grade box colour" to "#222222"
    And I press "Save changes"
    Then I should see "This colour is too dark for the text on it"
