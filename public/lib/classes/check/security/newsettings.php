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

/**
 * Checks for admin settings which have been added but never saved to config/config_plugins.
 *
 * @package    core
 * @category   check
 * @copyright  2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\check\security;

use core\check\check;
use core\check\result;

/**
 * Checks for admin settings which have been added but never saved to config/config_plugins.
 *
 * @package    core
 * @copyright  2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsettings extends check {
    /**
     * Get the short check name
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('check_newsettings_name', 'report_security');
    }

    /**
     * A link to a place to action this
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/index.php?cache=1'),
            get_string('notifications', 'admin')
        );
    }

    /**
     * Return result
     * @return result
     */
    public function get_result(): result {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $adminroot = admin_get_root();

        if (any_new_admin_settings($adminroot)) {
            $status = result::WARNING;
            $summary = get_string('check_newsettings_warning', 'report_security');
            return new newsettingsresult($status, $summary);
        }

        $status = result::OK;
        $summary = get_string('check_newsettings_ok', 'report_security');
        return new result($status, $summary);
    }
}
