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

namespace core_message\output;
use renderable;
use templatable;

/**
 * Renders the confirmation form for the one-click unsubscribe page.
 *
 * Submitting this form posts the body required by RFC 8058 section 3
 * (List-Unsubscribe=One-Click) back to the same URL.
 *
 * @package    core_message
 * @copyright  2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unsubscribe_confirm_form implements renderable, templatable {
    /**
     * Constructor for the confirmation form.
     * @param string $message The confirmation message to show the user.
     * @param string $actionurl The URL to POST the unsubscribe action to.
     * @param string $buttonlabel The label for the submit button.
     */
    public function __construct(
        /** @var string The confirmation message to show the user. */
        private string $message,
        /** @var string The URL to POST the unsubscribe action to. */
        private string $actionurl,
        /** @var string The label for the submit button. */
        private string $buttonlabel,
    ) {
    }

    /**
     * Exports the data for the template.
     *
     * @param \renderer_base $output The renderer to use for exporting any sub-templates.
     * @return array The data to be used in the template.
     */
    public function export_for_template(\renderer_base $output) {
        return [
            'message' => $this->message,
            'actionurl' => $this->actionurl,
            'buttonlabel' => $this->buttonlabel,
        ];
    }
}
