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
        $this->title = get_string('blocktitle', 'block_meter');
    }

    function get_content() {
        global $COURSE, $CFG, $USER, $OUTPUT, $SESSION, $DB;
        if($this->content !== NULL){
            return $this->content;
        }


        $this->content = new stdClass;

        if(!isset($this->config)){

            //why not also set the default weight here?
            $DB->set_field('block_instances', 'defaultweight', -2,
                array('id'=>$this->instance->id));

            $this->content->text .= 
                get_string('noconfigpresent', 'block_meter');

            return $this->content->text;
        }

        $graphurl = new moodle_url($CFG->wwwroot.'/blocks/meter/user_graph.php',
            array('id'=>$COURSE->id));

        if(has_capability('moodle/grade:viewall', $this->context)){
            $students = get_all_student_stats($COURSE->id);
            if(!$students){
                $this->content->text .= 'No activity data present. Please try again in 24 hours';
                return $this->content->text;
            }

            foreach ($students as $student){
                
                $graphurl->params(array('userid'=>$student->userid));

                $this->content->text .= 
                    html_writer::empty_tag('img', 
                    array('src' => $CFG->wwwroot.'/blocks/meter/pix/level'.
                    $student->level.'circle.png', 'class'=>'icon'));

                $this->content->text .= $OUTPUT->action_link($graphurl, 
                    $student->lastname.', '.$student->firstname).'<br />';
            }

            $graphurl->remove_params('userid');
            $this->content->text .= '<br /><p style="text-align: right;">'.
                $OUTPUT->action_link($graphurl, 
                get_string('viewallusers', 'block_meter')).'</p>';

        } else if(has_capability('mod/assignment:submit', $this->context)){

            $level = get_student_stats($USER->id, $COURSE->id);
            
            //assume they're avg(3)? Bigger prob here; student doesn't exist.
            if(!$level) $level = 3; 

            $graphurl->params(array('userid'=>$USER->id));
            $this->content->text .= $OUTPUT->action_icon($graphurl,
                        new pix_icon('level'.$level, get_string('viewgraph', 'block_meter'),
                        'block_meter'));

            $this->content->text .= '<br />';
            $this->content->text .= get_string('level'.$level.'user', 'block_meter');
        }

        return $this->content;
    }
    
    function instance_allow_multiple() {
        return false;
    }
    
    function has_config() {
        return true;
    }
    
    function instance_allow_config() {
        return true;
    }

    function cron() {
        global $CFG, $DB; 

        $lastcron = $DB->get_field('block', 'lastcron', 
            array('name'=>'meter'));

        $suffix='am';
        $cronhourdisp = $CFG->block_meter_cronhour;

        if($cronhourdisp > 12) $suffix='pm';
        $cronhourdisp .= $suffix;
        if($lastcron == 0){ //first time
            $lastcron = strtotime ('Yesterday '.$cronhourdisp);
        }


        $timenow = time();
        $crontime = usergetmidnight($timenow, $CFG->timezone) +
            ($CFG->block_meter_cronhour * 3600);

        if($lastcron < $crontime and $timenow > $crontime){
            $lock = $DB->get_record('block_meter_config',
                array('courseid'=>-1, 'name'=>'lock'));

            if($lock  &&  ($timenow - $lock->value) > (2 * 86400)){
                //should process -- the lock was set > 2 days ago
                //update the lock, and run the cron.
                mtrace('Found an old lock- discarding and running cron');
                $lock->value = $timenow;
                $DB->update_record('block_meter_config', $lock);

            } else if ($lock && ($timenow - $lock->value) < (2 * 86400)){
                //lock exists and it's fairly new ( < 2 days ago)
                //don't process.
                mtrace('Found a lock- not running cron');
                return;
            } else {
                //no lock found. Create one.
                mtrace('No lockfound - creating one and beginning cron');
                $lock = new stdClass();
                $lock->courseid = -1;
                $lock->name     = 'lock';
                $lock->value    = time(); 
                $lock->id = $DB->insert_record('block_meter_config', $lock);
            }
            
            //find all of the courses.
            $courses = $DB->get_records_select('block_meter_config', 'courseid != -1', null, '',
                'DISTINCT courseid');
            
            if(!$courses){
                mtrace('No courses found on which to run activity meter statistics');
            } else {
                foreach($courses as $course){ 
                    //a)    if dostatsrun flag in _config is set, delete the flag, and 
                    //      call a load_historical_data()  
                    //b)    call do_stats_run() or

                    if($DB->record_exists('block_meter_config',
                        array('courseid'=>$course->courseid, 'name'=>'dostatsrun'))){
                        mtrace('Incomplete do_stats_run for course id '.
                            $course->courseid.'. Loading historical data.');
                        
                        mtrace('Loading historical stats for course id '.
                            $course->courseid.'...', '');

                        load_historical_data($course->courseid, 0, 0, true);

                        $DB->delete_records('block_meter_config',
                            array('courseid'=>$course->courseid, 'name'=>'dostatsrun'));
                            
                        mtrace('Done.');
                    } else {
                        mtrace('Doing stats run for course id '.
                            $course->courseid.'...', '');
                        do_stats_run($course->courseid);
                        mtrace('Done.');
                    }
                }
            }
        }

        //update lastcron
        $DB->set_field('block', 'lastcron', strtotime('Today '.$cronhourdisp),
            array('name'=>'meter'));

        //remove lock
        $DB->delete_records('block_meter_config', 
            array('courseid'=>-1, 'name'=>'lock'));
    }

    /**
    * Override the instance_config_save method
    */
    function instance_config_save($data, $nolongerused = false){
        parent::instance_config_save($data, $nolongerused);

        global $DB, $COURSE;
        $config = get_meter_config($COURSE->id);
        $hasChanged = false;
        foreach ($data as $name => $value){

            $rec = $DB->get_record('block_meter_config',
                array('courseid'=>$COURSE->id,'name'=>$name));

            $conf = new stdClass;
            $conf->name = $name;
            $conf->value = $value;
            $conf->courseid= $COURSE->id;


            if($rec){
                $conf->id = $rec->id;
                if($config[$conf->name] != $conf->value) $hasChanged = true;
                $DB->update_record('block_meter_config',$conf);
            } else {
                $hasChanged = true;
                $DB->insert_record('block_meter_config',$conf);
            }

        }

        //check to see if this 'update' needs to trigger a load_historical_data()
        //load_historical_data() if it's the first time.
        //only load_historical_data if config values have changed.
        if($hasChanged){
            error_log("Meter: Config has changed - running load_historical_data()");
            load_historical_data($COURSE->id, 0, 0, true);
        }
    }


}
