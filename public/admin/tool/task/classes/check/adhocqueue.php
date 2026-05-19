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

namespace tool_task\check;

use action_link;
use core\check\check;
use core\check\result;
use moodle_url;

/**
 * Adhoc queue check.
 *
 * This alerts when the queue has old tasks in it which indicates that tasks
 * are not being processed fast enough and more processess need to be added
 * to manage the load. A large queue by itself is fine.
 *
 * @package    tool_task
 * @copyright  2020 Brendan Heywood (brendan@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adhocqueue extends check {
    /**
     * Default warning threshold for the age of the oldest unprocessed task in seconds.
     */
    private const DEFAULTWARNINGTHRESHOLD = 10 * MINSECS;

    /**
     * Default error threshold for the age of the oldest unprocessed task in seconds.
     */
    private const DEFAULTERRORTHRESHOLD = 4 * HOURSECS;

    /**
     * Method that gets the error theshold value.
     *
     * @return int
     */
    private function geterrorthreshold(): int {
        global $CFG;

        return $CFG->adhoctaskageerror ?? self::DEFAULTERRORTHRESHOLD;
    }

    /**
     * Method that gets the warning theshold value.
     *
     * @return int
     */
    private function getwarningthreshold(): int {
        global $CFG;

        return $CFG->adhoctaskagewarn ?? self::DEFAULTWARNINGTHRESHOLD;
    }

    /**
     * Return result
     * @return result
     */
    public function get_result(): result {
        global $DB, $CFG;

        $errorthreshold = $this->geterrorthreshold();

        $warningthreshold = $this->getwarningthreshold();

        $stats = $DB->get_record_sql(
            '
            SELECT count(*) cnt,
                   MAX(? - nextruntime) age
              FROM {task_adhoc}
             WHERE attemptsavailable > 0 OR attemptsavailable IS NULL',
            [time()]
        );

        $criticoverduecount = $DB->get_field_sql(
            '
            SELECT count(*)
              FROM {task_adhoc}
             WHERE nextruntime <= ? AND (attemptsavailable > 0 OR attemptsavailable IS NULL)',
            [time() - $errorthreshold]
        );

        $overduecount = $DB->get_field_sql(
            '
            SELECT count(*)
              FROM {task_adhoc}
             WHERE nextruntime > ? AND nextruntime <= ? AND (attemptsavailable > 0 OR attemptsavailable IS NULL)',
            [time() - $errorthreshold, time() - $warningthreshold]
        );

        $duecount = $DB->get_field_sql(
            '
            SELECT count(*)
              FROM {task_adhoc}
             WHERE nextruntime > ? AND nextruntime <= ? AND (attemptsavailable > 0 OR attemptsavailable IS NULL)',
            [time() - $warningthreshold, time()]
        );

        $futurecount = $DB->get_field_sql(
            '
            SELECT count(*)
              FROM {task_adhoc}
             WHERE nextruntime > ? AND (attemptsavailable > 0 OR attemptsavailable IS NULL)',
            [time()]
        );

        $status = result::OK;
        $summary = get_string('adhocempty', 'tool_task');
        $details = '';

        if ($stats->cnt > 0) {
            // A large queue size by itself is not an issue, only when tasks
            // are not being processed in a timely fashion is it an issue.
            $status = result::INFO;
            $summaryparts = new \stdClass();

            $summaryparts->criticallyoverdue = $criticoverduecount > 0
                ? get_string('criticallyoverduetaskscount', 'tool_task', $criticoverduecount)
                : '';

            $summaryparts->overdue = $overduecount > 0
                ? get_string('overduetaskscount', 'tool_task', $overduecount)
                : '';

            $summaryparts->due = get_string('duetaskscount', 'tool_task', $duecount);
            $summaryparts->future = get_string('futuretaskscount', 'tool_task', $futurecount);
            $summary = trim(get_string('taskssummary', 'tool_task', $summaryparts));

            $max = $CFG->adhoctaskagewarn ?? self::DEFAULTWARNINGTHRESHOLD;
            if ($stats->age > $max) {
                $status = result::WARNING;
                $details = get_string('adhocqueueold', 'tool_task', [
                    'age' => format_time($stats->age),
                    'max' => format_time($max),
                ]);
            }

            $max = $CFG->adhoctaskageerror ?? self::DEFAULTERRORTHRESHOLD;
            if ($stats->age > $max) {
                $status = result::ERROR;
                $details = get_string('adhocqueueold', 'tool_task', [
                    'age' => format_time($stats->age),
                    'max' => format_time($max),
                ]);
            }
        }

        return new result($status, $summary, $details);
    }

    /**
     * Link to the Ad hoc tasks report
     *
     * @return action_link|null
     */
    public function get_action_link(): ?action_link {
        return new action_link(
            new moodle_url('/admin/tool/task/adhoctasks.php'),
            get_string('adhoctasks', 'tool_task'),
        );
    }
}
