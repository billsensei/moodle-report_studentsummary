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
Feature: A student views their own summary report
  In order to see my progress
  As a student
  I need to open my own student summary report

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course A | CA        | 1                |
      | Course B | CB        | 0                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | CA     | student |
      | student1 | CB     | student |

  Scenario: A student opens their report from their profile
    Given I am on the "student1" "user > profile" page logged in as student1
    When I follow "Student summary"
    Then I should see "Student summary for Student One"
    And I should see "Activities completed"
    And I should see "Resources completed"
    And I should see "Courses enrolled"
    And I should see "Current grades"
    And I should see "Course A"
    And I should see "Course B"
    And I should see "Print"

  Scenario: A student with no courses sees an empty state
    Given the following "users" exist:
      | username | firstname | lastname |
      | student2 | Student   | Two      |
    When I am on the "student2" "user > profile" page logged in as student2
    And I follow "Student summary"
    Then I should see "No courses to show."
    And I should see "No courses to show."

  Scenario: The report applies a date range filter
    Given I am on the "student1" "user > profile" page logged in as student1
    And I follow "Student summary"
    When I set the following fields to these values:
      | id_from_enabled | 1 |
      | id_from_year    | 2026 |
    And I press "Apply"
    Then I should see "Date range"
    And I should see "Student summary for Student One"

  Scenario: An end date before the start date is rejected
    Given I am on the "student1" "user > profile" page logged in as student1
    And I follow "Student summary"
    When I set the following fields to these values:
      | id_from_enabled | 1    |
      | id_from_day     | 20   |
      | id_from_month   | 6    |
      | id_from_year    | 2026 |
      | id_to_enabled   | 1    |
      | id_to_day       | 1    |
      | id_to_month     | 6    |
      | id_to_year      | 2026 |
    And I press "Apply"
    Then I should see "The end date cannot be before the start date."
