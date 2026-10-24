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
Feature: Student summaries of a whole course
  In order to review and print every student's summary at once
  As a teacher
  I need one page with a summary for each student in my course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | teacher2 | Teacher   | Two      |
      | student1 | Alice     | Adams    |
      | student2 | Ben       | Brown    |
      | student3 | Cara      | Clark    |
    And the following "courses" exist:
      | fullname | shortname |
      | Course A | CA        |
      | Course B | CB        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | CA     | editingteacher |
      | teacher2 | CB     | editingteacher |
      | student1 | CA     | student        |
      | student2 | CA     | student        |
      | student3 | CB     | student        |

  Scenario: A teacher sees a summary for every student of the course
    Given I am on the "CA" "report_studentsummary > course" page logged in as teacher1
    Then I should see "Alice Adams" in the "region-main" "region"
    And I should see "Ben Brown" in the "region-main" "region"
    And I should not see "Cara Clark"
    And I should not see "Teacher One" in the "[data-region='report_studentsummary-course']" "css_element"

  Scenario: The course summaries are linked from the course reports page
    Given I am on the "CA" "course" page logged in as teacher1
    When I navigate to "Reports" in current page administration
    Then I should see "Student summaries (all students)"

  Scenario: A teacher of another course cannot open the page
    When I am on the "CA" "report_studentsummary > course" page logged in as teacher2
    Then I should not see "Alice Adams"
    And I should not see "Ben Brown"

  @javascript
  Scenario: Unticking a student leaves them out of the selection
    Given I am on the "CA" "report_studentsummary > course" page logged in as teacher1
    And I should see "2 of 2 selected"
    When I click on "Print Ben Brown" "checkbox"
    Then I should see "1 of 2 selected"
