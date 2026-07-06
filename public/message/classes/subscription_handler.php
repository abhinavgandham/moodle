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

/**
 * Interface for the subscription handler method that other providers may implement.
 *
 * @package    core_message
 * @category   backup
 * @copyright  2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface subscription_handler {
    /**
     * Handles the unsubscribe action for a specific user and subscriber.
     *
     * @param string $component The component the message provider belongs to.
     * @param string $messagetype The message type of the message provider.
     * @param int $userid The ID of the user to unsubscribe.
     * @param string $subscriptionkey Opaque, component-defined key identifying what to unsubscribe from.
     */
    public static function handle_unsubscribe(string $component, string $messagetype, int $userid, string $subscriptionkey): void;

    /**
     * Checks if the user is subscribed to the specific message provider.
     *
     * @param string $component The component the message provider belongs to.
     * @param string $messagetype The message type of the message provider.
     * @param int $userid The ID of the user to check subscription for.
     * @param string $subscriptionkey Opaque, component-defined key identifying what to unsubscribe from.
     */
    public static function is_subscribed(string $component, string $messagetype, int $userid, string $subscriptionkey): bool;

    /**
     * Gets the name of the subscriber for the specific message provider.
     *
     * @param string $component The component the message provider belongs to.
     * @param string $messagetype The message type of the message provider.
     * @param string $subscriptionkey Opaque, component-defined key identifying what to unsubscribe from.
     * @return string The display name of the subscriber.
     */
    public static function get_subscriber_name(string $component, string $messagetype, string $subscriptionkey): string;

    /**
     * Gives the handler a chance to customise the page (e.g. context, breadcrumb) before it is rendered.
     *
     * This runs on the unauthenticated one-click unsubscribe endpoint, so implementations must not assume
     * the current user is logged in, enrolled, or holds any capability in whatever context they set.
     *
     * @param \moodle_page $page The page to customise.
     * @param string $component The component the message provider belongs to.
     * @param string $messagetype The message type of the message provider.
     * @param string $subscriptionkey Opaque, component-defined key identifying what to unsubscribe from.
     */
    public static function set_page(\moodle_page $page, string $component, string $messagetype, string $subscriptionkey): void;
}
