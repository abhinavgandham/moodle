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
 * Result for the new admin settings check.
 *
 * @package    core
 * @category   check
 * @copyright  2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\check\security;

use core\check\result;

/**
 * Result for the new admin settings check.
 *
 * The list of unprocessed settings is only enumerated in get_details(), so the
 * full admin tree walk is deferred until someone actually drills into the check.
 *
 * @copyright  2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsettingsresult extends result {
    /**
     * Get the check detailed info.
     *
     * @return string formatted html
     */
    public function get_details(): string {
        $unprocessedsettings = $this->get_unprocessed_settings(admin_get_root());

        return get_string('newadminsettingspending', 'admin')
            . '<br>'
            . implode("<br>", array_map(fn($setting) => "<strong>" . s($setting) . "</strong>", $unprocessedsettings));
    }

    /**
     * Collects names of settings that have never been saved.
     *
     * @param \admin_category|\admin_settingpage $node
     * @return string[]
     */
    private function get_unprocessed_settings($node): array {
        $settings = [];
        $stack = [$node];

        while ($stack) {
            // Takes the most recently pushed node.
            $current = array_pop($stack);

            if ($current instanceof \admin_category) {
                // Queue items in a category to be processed later.
                foreach ($current->children as $child) {
                    $stack[] = $child;
                }
            } else if ($current instanceof \admin_settingpage) {
                // Look through admin_setting objects.
                foreach ($current->settings as $setting) {
                    // Add the setting if it is unprocessed.
                    if ($setting->get_setting() === null) {
                        $settings[] = $setting->plugin ? "{$setting->plugin}/{$setting->name}" : $setting->name;
                    }
                }
            }
        }

        return $settings;
    }
}
