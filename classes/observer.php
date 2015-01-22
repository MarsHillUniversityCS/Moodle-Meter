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
 * Event observer.
 *
 * @package    block_meter
 * @copyright  2015 Marty Gilbert
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_meter_observer {

    /**
     * Delete the meter data associated with the newly unenrolled user
     *
     * @param \core\event\base $event
     */
    public static function user_unenrolled(\core\event\base $event) {
        global $DB;

        $userenrollment = (object)$event->other['userenrolment'];

        $DB->delete_records('block_meter_studentstats', 
            array('studentid'=>$userenrollment->userid));

    }
}
