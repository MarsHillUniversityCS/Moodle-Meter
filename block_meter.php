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
 * This block will provide admin functions for Moodle Meter
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

            //why not just set the default config from global? TODO

            $this->content->text = 
                get_string('noconfigpresent', 'block_meter');
            

            return $this->content->text;
        }

        $graphurl = new moodle_url($CFG->wwwroot.'/blocks/meter/user_graph.php',
            array('id'=>$COURSE->id));

        if(has_capability('moodle/grade:viewall', $this->context)){
            $students = get_all_student_stats($COURSE->id);
            if(!$students){
                $this->content->text = get_string('noactivitypleasewait', 'block_meter');
                return $this->content->text;
            }

            $this->content->text = get_string('shortleveloverview', 'block_meter');
            $this->content->text .= '<br />';


            $count = 0;
            $moreurl = new moodle_url($CFG->wwwroot.'/blocks/meter/all_users.php',
                array('id'=>$COURSE->id));

            foreach ($students as $student){
                $graphurl->params(array('userid'=>$student->userid));

                $this->content->text .= 
                    html_writer::empty_tag('img', 
                    array('src' => $CFG->wwwroot.'/blocks/meter/pix/level'.
                    $student->level.'circle.png', 'class'=>'icon'));

                $this->content->text .= $OUTPUT->action_link($graphurl, 
                    $student->lastname.', '.$student->firstname).'<br />';

                $count++;
                if($count >= 15){ //don't show an exhaustive list


                    $this->content->text .= $OUTPUT->action_link($moreurl, 'More...');
                    break;
                }
            }

            $graphurl->remove_params('userid');
            $this->content->text .= '<br /><p style="text-align: right;">'.
                $OUTPUT->action_link($moreurl, 
                'View composite graph</p>');

        } else if(has_capability('mod/assignment:submit', $this->context)){

            $level = get_student_stats($USER->id, $COURSE->id);
            
            //assume they're avg(3)? Bigger prob here; student doesn't exist.
            if(!$level) $level = 3; 


            $this->content->text = get_string('level'.$level.'user', 'block_meter');
            $this->content->text .= '<br />';

            $graphurl->params(array('userid'=>$USER->id));
            $this->content->text .= $OUTPUT->action_icon($graphurl,
                        new pix_icon('level'.$level, get_string('viewgraph', 'block_meter'),
                        'block_meter', array('width'=>'250px')));

        }
        return $this->content;
    }
    
    function instance_allow_multiple() {
        return false;
    }

    public function applicable_formats() {
        return array(
            'course-view'   => true,
            'my'            => false);
    }
    
    function has_config() {
        return true;
    }
    
    function instance_allow_config() {
        return true;
    }

    /**
    * Override the instance_create in block_base
    */
    function instance_create(){
        //global $COURSE    

        $data = new stdClass();
        $globalconf = get_global_config();

        //load defaults weights
        foreach(range(1,6) as $i){
            if(!isset($config['tier'.$i.'_weight'])){
                $prop = 'tier'.$i.'_weight';
                $data->$prop = $globalconf['tier'.$i.'_weight'];
            }
        }
    
        if(!isset($config['default_weight']))
            $data->default_weight = $globalconf['default_weight'];

        $this->instance_config_save($data); 
        return parent::instance_create();
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

            $conf           = new stdClass;
            $conf->name     = $name;
            $conf->value    = $value;
            $conf->courseid = $COURSE->id;


            if($rec){
                $conf->id = $rec->id;
                if($config[$conf->name] != $conf->value) $hasChanged = true;
                $DB->update_record('block_meter_config', $conf);
            } else {
                $hasChanged = true;
                $result = $DB->insert_record('block_meter_config', $conf);
            }

        }


        //check to see if this 'update' needs to trigger a load_historical_data()
        //load_historical_data() if it's the first time.
        //only load_historical_data if config values have changed.
        if($hasChanged){
            load_historical_data($COURSE->id, 0, 0, true);
        }

        return;
    }

    function instance_delete() {

        //deleting the course removes ALL associated meter data

        $thiscoursecon = $this->context->get_course_context();
        $courseid = $thiscoursecon->instanceid;

        delete_all_course_data($courseid);
    }
}
