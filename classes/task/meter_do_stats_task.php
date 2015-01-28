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
 * A scheduled task for meter cron.
 *
 * @package    block_meter
 * @copyright  2015 Marty Gilbert <martygilbert@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_meter\task;

class meter_do_stats_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('crontask', 'block_meter');
    }

    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/meter/lib.php');
        meter_cron();
    }
}

?>
