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
 * Renders the page shown after a user has successfully unsubscribed using the one-click unsubscribe link.
 *
 * @package    core_message
 * @copyright  2026 Abhinav Gandham <abhinavgandham@catalyst-au.net>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unsubscribed_message implements renderable, templatable {
    /**
     * Constructor for the unsubscribed message.
     *
     * @param string $message The message to show the user after unsubscribing.
     */
    public function __construct(
        /** @var string The message to show the user after unsubscribing. */
        private string $message
    ) {
    }

    /**
     * Exports the data for the template.
     *
     * @param \renderer_base $output The renderer to use for exporting any sub-templates.
     * @return array The data to be used in the template.
     */
    public function export_for_template(\renderer_base $output): array {
        return [
            'message' => $this->message,
        ];
    }
}
