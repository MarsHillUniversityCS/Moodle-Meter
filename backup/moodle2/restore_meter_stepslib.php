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
 * @package meter
 * @copyright 2014 onwards Marty Gilbert {@link mailto:martygilbert@gmail.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that wll be used by the restore_meter_block_task
 */

/**
 * Define the complete meter  structure for restore
 */
class restore_meter_block_structure_step extends restore_structure_step {

    protected function define_structure() {

        $paths = array();

        $paths[] = new restore_path_element('block', '/block', true);
        $paths[] = new restore_path_element('meter', '/block/meter');
        $paths[] = new restore_path_element('config', '/block/meter/config');

        return $paths;
    }

    public function process_block($data) {
        global $DB;

        $data = (object)$data;

        // For any reason (non multiple, dupe detected...) block not restored, return
        if (!$this->task->get_blockid()) {
            return;
        }

        $courseid = $this->get_courseid();

        // Iterate over all the feed elements, creating them if needed
        if (isset($data->meter['config'])) {

            foreach ($data->meter['config'] as $conf) {
                $conf = (object)$conf;
                $conf->courseid = $courseid;

                $DB->insert_record('block_meter_config', $conf);
            }
        }
    }
}
