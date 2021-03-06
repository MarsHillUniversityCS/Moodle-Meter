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

require_once($CFG->dirroot . '/blocks/meter/backup/moodle2/backup_meter_stepslib.php'); // We have structure steps

/**
 * Specialised backup task for the meter block
 * (has own DB structures to backup)
 *
 * TODO: Finish phpdocs
 */
class backup_meter_block_task extends backup_block_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        // meter has one structure step
        $this->add_step(new backup_meter_block_structure_step('meter_structure', 'meter.xml'));
    }

    public function get_fileareas() {
        return array(); // No associated fileareas
    }

    public function get_configdata_encoded_attributes() {
        return array(); // No special handling of configdata
    }

    static public function encode_content_links($content) {
        return $content; // No special encoding of links
    }
}

