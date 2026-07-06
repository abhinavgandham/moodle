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
use core_message\default_subscription_handler;

/**
 * The class representing the unsubscribe link.
 *
 *
 * @package    core_message
 * @category   backup
 * @copyright  2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unsubscribe_link {
    /**
     * Constructor for the link. This is private to ensure that all links are created through create factory method.
     * @param int $userid The id of the user the link is for.
     * @param string $component The component the message provider belongs to.
     * @param string $messagetype The message type of the message provider.
     * @param string $token The unique token for this link.
     * @param string $subscriptionkey Component-defined key identifying what to unsubscribe from, if applicable.
     */
    private function __construct(
        /** @var int The id of the user the link is for. */
        private int $userid,
        /** @var string The component the message provider belongs to. */
        private string $component,
        /** @var string The message type of the message provider. */
        private string $messagetype,
        /** @var string The unique token for this link. */
        private string $token,
        /** @var string Component-defined key identifying what to unsubscribe from, if applicable. */
        private string $subscriptionkey = '',
    ) {
    }

    /**
     * Method that creates the unsubscribe link.
     *
     * @param int $userid The id of the user the link is for.
     * @param string $component The component the message provider belongs to.
     * @param string $messagetype The message type of the message provider.
     * @param string $subscriptionkey Component-defined key identifying what to unsubscribe from, if applicable.
     * @return ?self The unsubscribe link object, or null if a link cannot be generated
     */
    public static function create(int $userid, string $component, string $messagetype, string $subscriptionkey = ''): ?self {
        global $DB;

        $provider = $DB->get_record('message_providers', [
            'component' => $component,
            'name' => $messagetype,
        ], '*', MUST_EXIST);

        // Don't generate a link that can't actually do anything - admins can lock+force
        // the email channel for a provider, leaving the user no ability to opt out.
        $defaultpreferences = get_message_output_default_preferences();
        $lockedpref = 'email_provider_' . $component . '_' . $messagetype . '_locked';
        $enabledpref = 'message_provider_' . $component . '_' . $messagetype . '_enabled';
        $forced = !empty($defaultpreferences->{$lockedpref})
            && isset($defaultpreferences->{$enabledpref})
            && in_array('email', explode(',', $defaultpreferences->{$enabledpref}));
        if ($forced) {
            return null;
        }

        $providerid = $provider->id;
        $token = create_user_key('core_message/unsubscribe', $userid, $providerid, null, null);

        return new self($userid, $component, $messagetype, $token, $subscriptionkey);
    }


    /**
     * Method to create an unsubscribe link from the token.
     *
     * @param string $token The token from the unsubscribe link.
     * @param string $subscriptionkey Component-defined key identifying what to unsubscribe from, if applicable.
     * @return self The unsubscribe link object.
     */
    public static function from_token(string $token, string $subscriptionkey): self {
        global $DB;
        [$componentmessagetype, $itemkey] = array_pad(explode(':', $subscriptionkey, 2), 2, '');

        $provider = null;
        foreach ($DB->get_records('message_providers') as $candidate) {
            if ("{$candidate->component}_{$candidate->name}" === $componentmessagetype) {
                $provider = $candidate;
                break;
            }
        }
        if ($provider === null) {
            throw new \moodle_exception('invalidkey');
        }

        $userkey = validate_user_key($token, 'core_message/unsubscribe', $provider->id);

        return new self($userkey->userid, $provider->component, $provider->name, $token, $itemkey);
    }



    /**
     * Gets the id of the user the link is for.
     *
     * @return int The id of the user the link is for.
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Gets the display name of the message provider for this link.
     *
     * @return string The display name of the message provider.
     */
    public function get_provider_displayname(): string {
        return get_string('messageprovider:' . $this->messagetype, $this->component);
    }

    /**
     * Gets the most specific display name available for this link - the subscriber name
     * (e.g. a forum discussion title) if the component's handler supports it, otherwise
     * falls back to the message provider's display name.
     *
     * @return string The display name to show the user.
     */
    public function get_display_name(): string {
        $handlerclass = '\\' . $this->component . '\\message\\subscription_handler';
        if (!empty($this->subscriptionkey) && class_exists($handlerclass) && method_exists($handlerclass, 'get_subscriber_name')) {
            return $handlerclass::get_subscriber_name($this->component, $this->messagetype, $this->subscriptionkey);
        }
        return default_subscription_handler::get_subscriber_name($this->component, $this->messagetype, $this->subscriptionkey);
    }

    /**
     * Gets the URL for this unsubscribe link.
     *
     * @return \moodle_url The URL for this unsubscribe link.
     */
    public function get_url(): \moodle_url {
        $key = "{$this->component}_{$this->messagetype}";
        if (!empty($this->subscriptionkey)) {
            $key .= ":{$this->subscriptionkey}";
        }
        $params = ['token' => $this->token, 'subscriptionkey' => $key];
        return new \moodle_url('/message/unsubscribe.php', $params);
    }

    /**
     * Gets the headers to be added to the message for the one-click functionality to work.
     *
     * @return array The headers to be added to the message.
     */
    public function get_headers(): array {
        return [
            'List-Unsubscribe: <' . $this->get_url()->out(false) . '>',
            'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
        ];
    }

    /**
     * Checks if the user is currently subscribed to the message provider this link is for.
     *
     * @return bool Returns whether the user is subscribed or not.
     */
    public function is_subscribed(): bool {
        $handlerclass = '\\' . $this->component . '\\message\\subscription_handler';
        if (!empty($this->subscriptionkey) && class_exists($handlerclass) && method_exists($handlerclass, 'is_subscribed')) {
            return $handlerclass::is_subscribed($this->component, $this->messagetype, $this->userid, $this->subscriptionkey);
        }

        return default_subscription_handler::is_subscribed(
            $this->component,
            $this->messagetype,
            $this->userid,
            $this->subscriptionkey
        );
    }




    /**
     * Unsubscribes the user from the message provider this link is for.
     */
    public function unsubscribe(): void {
        $handlerclass = '\\' . $this->component . '\\message\\subscription_handler';
        if (!empty($this->subscriptionkey) && class_exists($handlerclass) && method_exists($handlerclass, 'handle_unsubscribe')) {
            $handlerclass::handle_unsubscribe($this->component, $this->messagetype, $this->userid, $this->subscriptionkey);
            return;
        }

        default_subscription_handler::handle_unsubscribe(
            $this->component,
            $this->messagetype,
            $this->userid,
            $this->subscriptionkey
        );
    }

    /**
     * Gives the component's handler a chance to customise the page before it is rendered.
     *
     * @param \moodle_page $page The page to customise.
     */
    public function set_page(\moodle_page $page): void {
        $handlerclass = '\\' . $this->component . '\\message\\subscription_handler';
        if (!empty($this->subscriptionkey) && class_exists($handlerclass) && method_exists($handlerclass, 'set_page')) {
            $handlerclass::set_page($page, $this->component, $this->messagetype, $this->subscriptionkey);
            return;
        }

        default_subscription_handler::set_page($page, $this->component, $this->messagetype, $this->subscriptionkey);
    }
}
