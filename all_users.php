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
 * This block will display a summary of hours and earnings for the worker.
 *
 * @package    Moodle Meter
 * @copyright  Marty Gilbert & Carter Benge
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once('lib.php');

require_login();

global $DB, $CFG, $COURSE;

$courseid = required_param('id', PARAM_INT);

$urlparams['id'] = $courseid;

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$PAGE->set_course($course);
$context = $PAGE->context;

require_capability('moodle/grade:viewall', $context);

$index = new moodle_url($CFG->wwwroot.'/blocks/meter/all_users.php', $urlparams);

$strtitle = get_string('pluginname','block_meter');

$PAGE->set_url($index);
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();


$students = get_all_student_stats($courseid);

if(!$students){
    echo get_string('noactivitypleasewait', 'block_meter');
} else {

    echo get_string('shortleveloverview', 'block_meter');
    echo '<br />';

    $graphurl = new moodle_url($CFG->wwwroot.'/blocks/meter/user_graph.php',
        array('id'=>$COURSE->id));

    foreach ($students as $student){
        $graphurl->params(array('userid'=>$student->userid));

        echo
            html_writer::empty_tag('img', 
            array('src' => $CFG->wwwroot.'/blocks/meter/pix/level'.
            $student->level.'circle.png', 'class'=>'icon'));

        echo $OUTPUT->action_link($graphurl, $student->lastname.', '.
            $student->firstname).'<br />';
    }
$graphurl->remove_params('userid');
echo '<br /><p>'.  $OUTPUT->action_link($graphurl, 
    get_string('viewallusers', 'block_meter')).'</p>';
}
echo $OUTPUT->footer();
