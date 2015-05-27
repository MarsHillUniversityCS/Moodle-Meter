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
 * An ad hoc task for meter cron - load historical stats
 *
 * @package    block_meter
 * @copyright  2015 Marty Gilbert <martygilbert@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_meter\task;

class meter_do_historical_stats_task extends \core\task\adhoc_task {

    public function get_name() {
        return get_string('historicaladhoc', 'block_meter');
    }

    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/blocks/meter/lib.php');
        $courseid   = -1;
        $start      = 0;
        $final      = 0;
        $deleteprev = false;


        $data = $this->get_custom_data();

        if(isset($data->courseid)){
            $courseid = $data->courseid;
        }

        if($courseid == -1) return;

        if(isset($data->start)){
            $start = $data->start;
        }

        if(isset($data->final)){
            $final = $data->final;
        }

        if(isset($data->deleteprev)){
            $deleteprev = $data->deleteprev;
        }

        cron_load_historical_data($courseid, $start, $final, $deleteprev);
    }
}

?>
