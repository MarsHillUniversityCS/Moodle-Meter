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
 * Define all the backup steps that wll be used by the backup_meter_block_task
 */

/**
 * Define the complete forum structure for backup, with file and id annotations
 */
class backup_meter_block_structure_step extends backup_block_structure_step {

    protected function define_structure() {
        global $DB;

        // Define each element separated
        $meter = new backup_nested_element('meter'); 
        $config = new backup_nested_element('config', array('id'), array(
            'courseid', 'name', 'value'));

        // Build the tree

        $meter->add_child($config);

        // Define sources
        //$meter->set_source_array(array((object)array('id' => $this->task->get_blockid())));
        $config->set_source_sql('SELECT * FROM {block_meter_config} WHERE courseid = ?',
            array(backup::VAR_COURSEID));

        // Annotations (none)

        // Return the root element (meter), wrapped into standard block structure
        return $this->prepare_block_structure($meter);
    }
}
