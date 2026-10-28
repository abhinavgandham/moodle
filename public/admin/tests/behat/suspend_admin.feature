@core @core_admin @core_user
Feature: Suspend a site administrator in one step
  In order to remove access for an old site administrator
  As an admin
  I need to be able to suspend another admin, which also removes them from the site administrators list

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | adminb   | Admin     | B        | adminb@example.com   |
      | adminc   | Admin     | C        | adminc@example.com   |
      | studentx | Student   | X        | studentx@example.com |
    And I log in as "admin"
    And I navigate to "Users > Permissions > Site administrators" in site administration
    And I set the field "addselect" to "Admin B (adminb@example.com), Admin C (adminc@example.com)"
    And I press "Add"
    And I press "Continue"
    And I should see "Admin B" in the "#removeselect" "css_element"
    And I should see "Admin C" in the "#removeselect" "css_element"

  @javascript
  Scenario: The suspend action is available for other admins but not for my own account
    When I navigate to "Users > Accounts > Browse list of users" in site administration
    Then the "Suspend user account" item should exist in the "Actions" action menu of the "Admin B" "table_row"
    And the "Suspend user account" item should not exist in the "Actions" action menu of the "Admin User" "table_row"

  @javascript
  Scenario: Cancelling the suspension of an admin from the user list leaves them unchanged
    Given I navigate to "Users > Accounts > Browse list of users" in site administration
    When I press "Suspend user account" action in the "Admin B" report row
    Then I should see "Are you sure you want to suspend Admin B? This will remove them from the list of site administrators and suspend their account."
    And I press "Cancel"
    And I should see "Browse list of users"
    And I should not see "Suspended account" in the "Admin B" "table_row"
    And I navigate to "Users > Permissions > Site administrators" in site administration
    And I should see "Admin B" in the "#removeselect" "css_element"

  @javascript
  Scenario: Confirming the suspension of an admin from the user list suspends them and removes admin
    Given I navigate to "Users > Accounts > Browse list of users" in site administration
    When I press "Suspend user account" action in the "Admin B" report row
    And I press "Suspend"
    Then I should see "Suspended account" in the "Admin B" "table_row"
    And I navigate to "Users > Permissions > Site administrators" in site administration
    And I should not see "Admin B" in the "#removeselect" "css_element"
    And I should see "Admin B" in the "#addselect" "css_element"
    # Admin C was not touched.
    And I should see "Admin C" in the "#removeselect" "css_element"

  @javascript
  Scenario: Suspending a regular user from the user list does not ask for confirmation
    Given I navigate to "Users > Accounts > Browse list of users" in site administration
    When I press "Suspend user account" action in the "Student X" report row
    Then I should not see "This will remove them from the list of site administrators"
    And I should see "Suspended account" in the "Student X" "table_row"

  Scenario: The suspended checkbox is enabled for other admins but frozen for my own account
    When I am on the "adminc" "user > editing" page
    Then the "Suspended account" "field" should be enabled
    And I am on the "admin" "user > editing" page
    And the "Suspended account" "field" should be disabled

  Scenario: Cancelling the suspension of an admin from the edit page saves nothing
    Given I am on the "adminc" "user > editing" page
    When I set the field "Suspended account" to "1"
    And I press "Update profile"
    Then I should see "Are you sure you want to suspend Admin C? This will remove them from the list of site administrators and suspend their account."
    And I press "Cancel"
    And the field "Suspended account" matches value "0"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should not see "Suspended account" in the "Admin C" "table_row"
    And I navigate to "Users > Permissions > Site administrators" in site administration
    And I should see "Admin C" in the "#removeselect" "css_element"

  Scenario: Confirming the suspension of an admin from the edit page suspends them and removes admin
    Given I am on the "adminc" "user > editing" page
    When I set the field "Suspended account" to "1"
    And I press "Update profile"
    And I press "Suspend"
    Then I should see "Suspended account" in the "Admin C" "table_row"
    And I navigate to "Users > Permissions > Site administrators" in site administration
    And I should not see "Admin C" in the "#removeselect" "css_element"
    And I should see "Admin C" in the "#addselect" "css_element"
    And I should see "Admin B" in the "#removeselect" "css_element"

  Scenario: Suspending a regular user from the edit page does not ask for confirmation
    Given I am on the "studentx" "user > editing" page
    When I set the field "Suspended account" to "1"
    And I press "Update profile"
    Then I should not see "This will remove them from the list of site administrators"
    And I should see "Suspended account" in the "Student X" "table_row"
    And I am on the "studentx" "user > editing" page
    And the field "Suspended account" matches value "1"

  @javascript
  Scenario: A suspended admin cannot log in and is not made admin again when unsuspended
    Given I navigate to "Users > Accounts > Browse list of users" in site administration
    And I press "Suspend user account" action in the "Admin B" report row
    And I press "Suspend"
    And I log out
    When I log in as "adminb"
    Then I should see "Invalid login"
    And I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I press "Activate user account" action in the "Admin B" report row
    And I should not see "Suspended account" in the "Admin B" "table_row"
    And I navigate to "Users > Permissions > Site administrators" in site administration
    And I should not see "Admin B" in the "#removeselect" "css_element"
    And I log out
    And I log in as "adminb"
    And I should not see "Invalid login"
    And "Site administration" "link" should not exist in the ".primary-navigation" "css_element"
