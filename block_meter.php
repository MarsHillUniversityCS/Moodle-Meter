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
 * This block will provide admin functions for TimeTracker
 *
 * @package    Block
 * @subpackage Meter
 * @copyright  2014 Carter Benge, Marty Gilbert
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

//require_once('lib.php');
require_once($CFG->dirroot.'/blocks/meter/lib.php');

class block_meter extends block_base {

    function init() {
        global $USER;
        if(is_siteadmin($USER->id)){
            $this->title = get_string('blocktitle', 'block_meter');
        }
    }

    function get_content() {
        //global $CFG, $DB, $USER, $OUTPUT, $COURSE;
        global $COURSE;
        if($this->content !== NULL){
            return $this->content;
        }

        $this->content = new stdClass;

        $this->content->text    .= '<h4>The Moodle Meter Block</h4>';
        $this->content->text    .= '<br />';//.$COURSE->id;

        //do_stats_run($COURSE->id);
        if(has_capability('moodle/grade:viewall', $this->context)){
            //$this->content->text    .= '<br />You are a teacher';
            $students = get_student_stats($COURSE->id);
            foreach ($students as $student){
                $this->content->text .= $student->lastname.', '.$student->firstname.
                    ' - Level '.$student->level.'<br />';
            }
        } else if(has_capability('mod/assignment:submit', $this->context)){
            $this->content->text    .= '<br />You are a student';
        }

        return $this->content;
    }
    
    function instance_allow_multiple() {
        return false;
    }
    
    function has_config() {
        return false;
    }
    
    function instance_allow_config() {
        return true;
    }

    function cron() {
        global $CFG, $DB; 

        $lastcron = $DB->get_field('block', 'lastcron', 
            array('name'=>'meter'));

        if($lastcron == 0){
            $yesterday = strtotime ("Yesterday 6am");
            $DB->set_field('block', 'lastcron', $yesterday,
                array('name'=>'meter'));
            return;
        }

        //update cron to execute in the morning at 6am - ish
        $DB->set_field('block', 'lastcron', strtotime("Today 6am"),
            array('name'=>'meter'));
    }

}
