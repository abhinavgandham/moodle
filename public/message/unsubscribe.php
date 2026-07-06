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
 * One-click email unsubscribe endpoint, covering all message providers.
 *
 * @package   core_message
 * @copyright 2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Public one-click unsubscribe endpoint (RFC 8058); email clients POST here unauthenticated.
require(__DIR__ . '/../config.php');
use core_message\unsubscribe_link;
use core_message\output\unsubscribed_message;
use core_message\output\unsubscribe_confirm_form;

// Validate the token.
$token = required_param('token', PARAM_ALPHANUM);
$subscriptionkey = required_param('subscriptionkey', PARAM_RAW_TRIMMED);

// Creating the one-click unsubscribe link with the provided token and subscription key.
try {
    $link = unsubscribe_link::from_token($token, $subscriptionkey);
} catch (moodle_exception $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(400);
        die('Invalid or expired token.');
    }
    throw $e;
}

$pageurl = $link->get_url();
$PAGE->set_url($pageurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('unsubscribe', 'message'));
$link->set_page($PAGE);
$PAGE->set_secondary_navigation(false);

// Handle one click unsubscribe behavior.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // PHP parses both application/x-www-form-urlencoded and multipart/form-data bodies into
    // $_POST, so this works regardless of which encoding the mail client chooses (RFC 8058 s3).
    if (($_POST['List-Unsubscribe'] ?? null) !== 'One-Click') {
        http_response_code(400);
        die('Missing required post body.');
    }
    $link->unsubscribe();

    $unsubscribeduser = core_user::get_user($link->get_userid());
    $unsubscribedmessage = new unsubscribed_message(
        get_string('unsubscribed', 'message', (object) [
            'email' => $unsubscribeduser->email,
            'component' => $link->get_display_name(),
        ])
    );

    echo $OUTPUT->header();
    echo $OUTPUT->render($unsubscribedmessage);
    echo $OUTPUT->footer();
    exit;
} else {
    echo $OUTPUT->header();

    if (!$link->is_subscribed()) {
        $unsubscribeduser = core_user::get_user($link->get_userid());
        echo $OUTPUT->notification(get_string('alreadyunsubscribed', 'message', (object) [
            'email' => $unsubscribeduser->email,
            'component' => $link->get_display_name(),
        ]), 'info');
        echo $OUTPUT->footer();
        exit;
    }

    // Render a form so the POST body contains List-Unsubscribe=One-Click (RFC 8058 section 3).
    $recipient = core_user::get_user($link->get_userid());
    $confirmform = new unsubscribe_confirm_form(
        get_string('confirmunsubscribe', 'message', (object) [
            'component' => $link->get_display_name(),
            'email' => $recipient->email,
        ]),
        $pageurl->out(false),
        get_string('unsubscribe', 'message')
    );
    echo $OUTPUT->render($confirmform);

    echo $OUTPUT->footer();
}
