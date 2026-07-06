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
 * This file defines a handler class for one-click unsubscribe from forum discussions.
 *
 * @package    mod_forum
 * @copyright  2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\message;
use mod_forum\subscriptions;

/**
 * Handler class for one-click unsubscribe from forum discussions.
 * @package    mod_forum
 * @copyright  2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subscription_handler implements \core_message\subscription_handler {
    /**
     * Handles the one-click unsubscribe action for a specific user and forum discussion instance.
     *
     * @param string $component The component the message provider belongs to. Unused: always mod_forum here.
     * @param string $messagetype The message type of the message provider. Unused: always posts here.
     * @param int $userid The ID of the user to unsubscribe.
     * @param string $subscriptionkey The discussion id for the forum discussion.
     */
    public static function handle_unsubscribe(string $component, string $messagetype, int $userid, string $subscriptionkey): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/forum/lib.php');
        $discussion = $DB->get_record('forum_discussions', ['id' => (int) $subscriptionkey], '*', MUST_EXIST);
        subscriptions::unsubscribe_user_from_discussion($userid, $discussion);
    }

    /**
     * Checks if a specific user is subscribed to a forum discussion.
     *
     * @param string $component The component the message provider belongs to. Unused: always mod_forum here.
     * @param string $messagetype The message type of the message provider. Unused: always posts here.
     * @param int $userid The ID of the user to check subscription for.
     * @param string $subscriptionkey The discussion id for the forum discussion.
     * @return bool True if the user is subscribed, false otherwise.
     */
    public static function is_subscribed(string $component, string $messagetype, int $userid, string $subscriptionkey): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/forum/lib.php');
        $discussion = $DB->get_record('forum_discussions', ['id' => (int) $subscriptionkey], '*', MUST_EXIST);
        $forum = $DB->get_record('forum', ['id' => $discussion->forum], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('forum', $forum->id, $forum->course, false, MUST_EXIST);
        return subscriptions::is_subscribed($userid, $forum, $discussion->id, $cm);
    }


    /**
     * Retrieves the name of the subscriber (forum discussion).
     *
     * @param string $component The component the message provider belongs to. Unused: always mod_forum here.
     * @param string $messagetype The message type of the message provider. Unused: always posts here.
     * @param string $subscriptionkey The discussion id for the forum discussion.
     * @return string The name of the forum discussion.
     */
    public static function get_subscriber_name(string $component, string $messagetype, string $subscriptionkey): string {
        global $DB;
        $discussion = $DB->get_record('forum_discussions', ['id' => (int) $subscriptionkey], '*', MUST_EXIST);
        return format_string($discussion->name);
    }

    /**
     * Sets the module context on the page so the unsubscribe page picks up the forum's breadcrumb and navigation.
     *
     * Runs on the unauthenticated one-click unsubscribe endpoint, so this must not gate on require_login().
     *
     * @param \moodle_page $page The page to customise.
     * @param string $component The component the message provider belongs to. Unused: always mod_forum here.
     * @param string $messagetype The message type of the message provider. Unused: always posts here.
     * @param string $subscriptionkey The discussion id for the forum discussion.
     */
    public static function set_page(\moodle_page $page, string $component, string $messagetype, string $subscriptionkey): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/forum/lib.php');
        $discussion = $DB->get_record('forum_discussions', ['id' => (int) $subscriptionkey], '*', MUST_EXIST);
        $forum = $DB->get_record('forum', ['id' => $discussion->forum], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('forum', $forum->id, $forum->course, false, MUST_EXIST);
        $page->set_cm($cm, null, $forum);
        $page->activityheader->set_attrs(['description' => '']);
    }
}
