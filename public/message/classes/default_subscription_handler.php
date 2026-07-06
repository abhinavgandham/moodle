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

namespace core_message;
use core_message\subscription_handler;

/**
 * Default subscription handler class.
 *
 * @package    core_message
 * @category   backup
 * @copyright  2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class default_subscription_handler implements subscription_handler {
    /**
     * Method for the default unsubscribe behavior if a provider does not have a custom subscription handler.
     *
     * @param string $component The component the message provider belongs to.
     * @param string $messagetype The message type of the message provider.
     * @param int $userid The ID of the user to unsubscribe.
     * @param string $subscriptionkey Component-defined key identifying what to unsubscribe from.
     */
    public static function handle_unsubscribe(string $component, string $messagetype, int $userid, string $subscriptionkey): void {
        $preferencename = 'message_provider_' . $component . '_' . $messagetype . '_enabled';

        $current = get_user_preferences($preferencename, null, $userid);
        if ($current === null) {
            $defaultpreferences = get_message_output_default_preferences();
            $current = $defaultpreferences->{$preferencename} ?? '';
        }

        $remaining = array_diff(explode(',', $current), ['email']);
        set_user_preference($preferencename, $remaining ? implode(',', $remaining) : 'none', $userid);
    }

    /**
     * Method for the default is_subscribed behavior if a provider does not have a custom subscription handler.
     *
     * @param string $component The component the message provider belongs to.
     * @param string $messagetype The message type of the message provider.
     * @param int $userid The ID of the user to check subscription for.
     * @param string $subscriptionkey Component-defined key identifying what to check subscription for.
     * @return bool True if the user is subscribed, false otherwise.
     */
    public static function is_subscribed(string $component, string $messagetype, int $userid, string $subscriptionkey): bool {
        $preferencename = 'message_provider_' . $component . '_' . $messagetype . '_enabled';
        $userpreference = get_user_preferences($preferencename, null, $userid);
        if ($userpreference !== null) {
            return in_array('email', explode(',', $userpreference));
        }

        $defaultpreferences = get_message_output_default_preferences();
        return isset($defaultpreferences->{$preferencename})
            && in_array('email', explode(',', $defaultpreferences->{$preferencename}));
    }

    /**
     * Method for the default get_subscriber_name behavior if a provider does not have a custom subscription handler.
     *
     * @param string $component The component the message provider belongs to.
     * @param string $messagetype The message type of the message provider.
     * @param string $subscriptionkey Component-defined key identifying what to get subscriber name for.
     * @return string The display name of the subscriber.
     */
    public static function get_subscriber_name(string $component, string $messagetype, string $subscriptionkey): string {
        return get_string('messageprovider:' . $messagetype, $component);
    }

    /**
     * Method for the default set_page behavior if a provider does not have a custom subscription handler.
     *
     * @param \moodle_page $page The Moodle page object to set.
     * @param string $component The component the message provider belongs to.
     * @param string $messagetype The message type of the message provider.
     * @param string $subscriptionkey Component-defined key identifying what to set page for.
     */
    public static function set_page(\moodle_page $page, string $component, string $messagetype, string $subscriptionkey): void {
    }
}
