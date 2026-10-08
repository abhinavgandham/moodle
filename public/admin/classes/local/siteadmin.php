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

namespace core_admin\local;

/**
 * Site admin class that manages the site admins list and provides methods to operate on the list.
 *
 * @package     core_admin
 * @copyright   2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class siteadmin {
    /**
     * Returns the list of site admins as an array of user ids.
     *
     * @return array An array containing the user ids of site admins.
     */
    public static function get_admins(): array {
        global $CFG;
        $admins = [];
        foreach (explode(',', $CFG->siteadmins) as $admin) {
            $admin = (int)$admin;
            if ($admin) {
                $admins[$admin] = $admin;
            }
        }
        return $admins;
    }

    /**
     * Removes the specified user ids from the site admins list.
     *
     * @param string $removals A comma-separated string of user ids to be removed from the site admins list.
     * @param bool $checkcfgsiteadmins A boolean indicating whether to check if site admins are defined in config.php.
     */
    public static function remove_admins(string $removals, bool $checkcfgsiteadmins = false): void {
        global $CFG, $USER;

        if ($checkcfgsiteadmins) {
            // Do not do anything if the siteadmins are defined in config.php.
            if (array_key_exists('siteadmins', $CFG->config_php_settings)) {
                return;
            }
        }

        $admins = self::get_admins();
        $logstringold = implode(', ', $admins);

        // Can not remove self.
        foreach (explode(',', $removals) as $userid) {
            if ($userid != $USER->id) {
                unset($admins[$userid]);
            }
        }

        $logstringnew = implode(', ', $admins);

        set_config('siteadmins', implode(',', $admins));
        add_to_config_log('siteadmins', $logstringold, $logstringnew, 'core');
    }

    /**
     * Suspends a user, removing them from the site admins list first if they are a site admin.
     *
     * Only site admins can suspend other site admins. When the user is a site admin and the suspension has not
     * been confirmed yet, the confirmation page is printed and the script ends.
     *
     * @param int $userid The id of the user to suspend.
     * @param string $confirm The confirmation hash (md5 of the user id) sent back by the confirmation page.
     * @param \moodle_url $returnurl The page to go back to when the confirmation page is cancelled.
     */
    public static function suspend_user(int $userid, string $confirm, \moodle_url $returnurl): void {
        global $CFG, $DB, $USER, $OUTPUT, $PAGE;

        $user = $DB->get_record('user', ['id' => $userid, 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0]);
        if (!$user || $user->id == $USER->id || $user->suspended == 1) {
            return;
        }

        $isadmin = is_siteadmin($user->id);
        if ($isadmin && !is_siteadmin()) {
            // Only site admins can suspend (and remove admin rights from) other site admins.
            return;
        }

        if ($isadmin) {
            if ($confirm != md5($userid)) {
                $yesurl = new \moodle_url($returnurl, [
                    'suspend' => $userid,
                    'confirm' => md5($userid),
                    'sesskey' => sesskey(),
                ]);
                echo $OUTPUT->header();
                echo $PAGE->get_renderer('core', 'admin')->suspend_admin_confirm($user, $yesurl, $returnurl);
                echo $OUTPUT->footer();
                die;
            }
            self::remove_admins((string)$user->id, true);
        }

        $user->suspended = 1;
        // Force logout.
        \core\session\manager::destroy_user_sessions($user->id);
        \core\user::update_user($user, false);
    }
}
