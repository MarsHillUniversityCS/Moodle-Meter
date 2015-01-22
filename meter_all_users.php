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
 * This form will allow administration to batch sign timesheets electronically and export to payroll.  *
 * @package    Block
 * @subpackage TimeTracker
 * @copyright  2011 Marty Gilbert & Brad Hughes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

require_once("$CFG->libdir/formslib.php");
require_once('lib.php');

class meter_all_users extends moodleform {

    function meter_all_users ($students){
        $this->students = $students;
        parent::__construct();
    }

    function definition() {
        global $CFG, $COURSE, $OUTPUT;

        $mform =& $this->_form;
        
        $mform->addElement('hidden', 'id', $COURSE->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'general', 'All students');

        $graphurl = new moodle_url($CFG->wwwroot.'/blocks/meter/user_graph.php',
            array('id'=>$COURSE->id));

        $this->add_checkbox_controller(1);
        //$mform->addElement('html', '<table><tr><th width="5%">Select</th><th>Student</th></tr>');
        foreach ($this->students as $student){
            $graphurl->params(array('userid'=>$student->userid));

            $desc = ' '.html_writer::empty_tag('img', 
                array('src' => $CFG->wwwroot.'/blocks/meter/pix/level'.
                $student->level.'circle.png', 'class'=>'icon')).' '.
                $OUTPUT->action_link($graphurl, $student->lastname.', '.
                $student->firstname);

            $mform->addElement('advcheckbox', 
                'studentid'.'['.$student->id.']','', $desc, array('group'=>1));

        }

        $buttonarray=array();
        $buttonarray[] =
            &$mform->createElement('submit', 'viewgraphbutton', 'View graph');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

    }

    function validation($data, $files){
        error_log("in validation. sizeof is: ".sizeof($data['studentid']));
        error_log(print_r($data['studentid'], true));
        $errors = array();
        if(sizeof($data['studentid']) == 0) return $errors;

        $count = 0;

        foreach($data['studentid'] as $ids){
            if($ids == 1) $count++;
        }

        foreach($data['studentid'] as $key=>$val) break;
        //hack, because I have no normal elements to attach the error to
        $key = 'studentid['.$key.']';

        error_log("count is $count");

        if($count > 20){
            $errors[$key] = 'Select no more than 20 students';
        } else if ($count < 1) {
            error_log("must be at least 1");
            $errors[$key] = 'Must select at least 1 student';
        }

        error_log(print_r($errors, true));

        return $errors;
    }
}
?>
