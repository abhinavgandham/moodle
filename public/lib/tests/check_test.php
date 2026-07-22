<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace core;

use core\check\result;
use core\check\security\passwordpolicy;
use core\check\security\newsettings;

/**
 * Example unit tests for check API
 *
 * @package    core
 * @category   check
 * @copyright  2020 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \core\check\check
 */
final class check_test extends \advanced_testcase {
    /**
     * A simple example showing how a check and result object works
     *
     * Conceptually a check is analgous to a unit test except at runtime
     * instead of build time so many checks in real life such as testing
     * an API is connecting aren't viable to unit test.
     */
    public function test_passwordpolicy(): void {
        global $CFG;
        $prior = $CFG->passwordpolicy;

        $check = new passwordpolicy();

        $CFG->passwordpolicy = false;
        $result = $check->get_result();
        $this->assertEquals($result->get_status(), result::WARNING);

        $CFG->passwordpolicy = true;
        $result = $check->get_result();
        $this->assertEquals($result->get_status(), result::OK);

        $CFG->passwordpolicy = $prior;
    }

    /**
     * Tests that the component is correctly set.
     */
    public function test_get_component(): void {
        $check = new \tool_task\check\maxfaildelay();

        // If no component is set, it should return the one based off the namespace.
        $this->assertEquals('tool_task', $check->get_component());

        // However if one is set, it should return that.
        $check->set_component('test component');
        $this->assertEquals('test component', $check->get_component());
    }

    /**
     * Tests that the newsettings check reports a warning when a setting is unset.
     */
    public function test_newsettings_warning(): void {
        global $CFG;
        $this->resetAfterTest();

        // Setting these settings as they are not set in the phpunit environment.
        set_config('supportemail', 'support@example.com');
        $frontpage = new \admin_setting_special_frontpagedesc();
        $frontpage->write_setting('test frontpage description');

        unset($CFG->passwordpolicy);

        $check = new newsettings();
        $result = $check->get_result();
        $this->assertEquals($result->get_status(), result::WARNING);
    }

    /**
     * Tests that the newsettings check reports OK when all settings are set.
     */
    public function test_newsettings_ok(): void {
        $this->resetAfterTest();

        // Setting these settings as they are not set in the phpunit environment.
        set_config('supportemail', 'support@example.com');
        $frontpage = new \admin_setting_special_frontpagedesc();
        $frontpage->write_setting('test frontpage description');

        $check = new newsettings();
        $result = $check->get_result();
        $this->assertEquals($result->get_status(), result::OK);
    }
}
