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
Feature: Access control for the student summary report
  In order to protect student data
  As a site administrator
  I need only permitted users to see a student's summary report

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | teacher2 | Teacher   | Two      |
      | student1 | Student   | One      |
      | student2 | Student   | Two      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course A | CA        |
      | Course B | CB        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | CA     | editingteacher |
      | teacher2 | CB     | editingteacher |
      | student1 | CA     | student        |
      | student2 | CB     | student        |

  Scenario: A teacher views the report of a student in their course
    Given I am on the "student1" "user > profile" page logged in as teacher1
    When I follow "Student summary"
    Then I should see "Student summary for Student One"
    And I should see "Course A"

  Scenario: A teacher cannot see a student who is not in their courses
    Given I am on the "student1" "user > profile" page logged in as teacher2
    Then I should not see "Student summary" in the "region-main" "region"

  Scenario: A student cannot open another student's report
    Given I log in as "student1"
    And I am on the "student2" "user > profile" page
    Then I should not see "Student summary" in the "region-main" "region"

  Scenario: An administrator views any student's report
    Given I am on the "student2" "user > profile" page logged in as admin
    When I follow "Student summary"
    Then I should see "Student summary for Student Two"
    And I should see "Course B"

  Scenario: A teacher in separate groups only sees students in a shared group
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher3 | Teacher   | Three    |
    And the following "courses" exist:
      | fullname | shortname | groupmode | groupmodeforce |
      | Course G | CG        | 1         | 1              |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher3 | CG     | teacher        |
      | student1 | CG     | student        |
      | student2 | CG     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | CG     | G1       |
      | Group 2 | CG     | G2       |
    And the following "group members" exist:
      | user     | group |
      | teacher3 | G1    |
      | student1 | G1    |
      | student2 | G2    |
    And the following "permission overrides" exist:
      | capability                  | permission | role    | contextlevel | reference |
      | moodle/site:accessallgroups | Prohibit   | teacher | Course       | CG        |
    When I am on the "student1" "user > profile" page logged in as teacher3
    Then I should see "Student summary" in the "region-main" "region"
    And I am on the "student2" "user > profile" page
    And I should not see "Student summary" in the "region-main" "region"
