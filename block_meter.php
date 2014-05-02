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
        global $COURSE, $CFG, $USER, $OUTPUT, $SESSION;
        if($this->content !== NULL){
            return $this->content;
        }


        $this->content = new stdClass;

        if(!isset($this->config)){

            $configurl = new moodle_url($CFG->wwwroot.
                '/course/view.php', array('id'=>$COURSE->id, 'sesskey'=>sesskey(),
                'bui_editid'=>$this->instance->id));
            
            $this->content->text .= $OUTPUT->action_link($configurl, get_string('noconfigpresent', 'block_meter'));

            return $this->content->text;

        }


        /*
        $this->content->text    .= '<h4>The Moodle Meter Block</h4>';
        $this->content->text    .= '<br />';//.$COURSE->id;
        */
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
            $this->content->text .= '<br /><p style="text-align: right;">'.$OUTPUT->action_link($graphurl, 
                get_string('viewallusers', 'block_meter')).'</p>';

        } else if(has_capability('mod/assignment:submit', $this->context)){

            $level = get_student_stats($USER->id, $COURSE->id);

        
            /*
            $this->content->text .= 
                html_writer::empty_tag('img', 
                array('src' => $CFG->wwwroot.'/blocks/meter/pix/level'.$level.'.png',
                    'alt'=>get_string('viewgraph', 'block_meter')));
            */


            
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

    /**
    * Override the instance_config_save method
    */
    function instance_config_save($data, $nolongerused = false){
        parent::instance_config_save($data, $nolongerused);
        global $DB, $COURSE;
        foreach ($data as $name => $value){

            $rec = $DB->get_record('block_meter_config',
                array('courseid'=>$COURSE->id,'name'=>$name));

            $conf = new stdClass;
            $conf->name = $name;
            $conf->value = $value;
            $conf->courseid= $COURSE->id;

            if($rec){
                $conf->id = $rec->id;
                $DB->update_record('block_meter_config',$conf);
            } else {
                $DB->insert_record('block_meter_config',$conf);
            }

        }
    }


}
