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

global $DB, $CFG, $COURSE, $USER;

$courseid = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_TEXT); //urlencoded csv of ids

$urlparams['id'] = $courseid;
$urlparams['userid'] = $userid;

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$PAGE->set_course($course);
$context = $PAGE->context;

$isteacher = false;
if (has_capability('moodle/grade:viewall', $context)) { //teacher
    $isteacher = true;
}

$studentids = urldecode($userid);
$studentids = explode(',', $studentids);
//error_log(print_r($studentids, true));

if(!$isteacher && $sizeof($studentids) > 1){
    print_error('improper permissions');
}

if(!$isteacher && $studentids[0] != $USER->id){
    print_error('improper permissions');
}

$index = new moodle_url($CFG->wwwroot.'/blocks/meter/user_graph.php', $urlparams);

$strtitle = get_string('pluginname','block_meter');

$PAGE->set_url($index);
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);
//$PAGE->set_pagelayout('home');

echo $OUTPUT->header();

//don't write the graph unless there is data? Show a message otherwise.
$statsforcourse = $DB->get_records('block_meter_stats',
    array('courseid'=>$COURSE->id), '', 'id');

//error_log(sizeof($statsforcourse));
if($statsforcourse && sizeof($statsforcourse) > 1){

    $numRecs = $DB->count_records_select('block_meter_studentstats',
        'statsid in ('.implode(',', array_keys($statsforcourse)).')');

    if($numRecs > 0){
        $imageurl = new moodle_url('/blocks/meter/graph.php', 
            array('id'=>$courseid, 'userid'=>$userid));
        $graph = html_writer::empty_tag('img', 
            array('src' => $imageurl, 'alt'=>'Moodle Meter Graph',
            'class'=>'meter_graph'));

        echo html_writer::tag('div', $graph, array('class' => 'meter_graph'));
    } else
        //error_log('numRecs is 0');
        echo ("<h3>Not enough student data to produce a graph at this time.</h3>");

} else {
    //error_log('not enough stats');
    echo ("<h3>Not enough student data to produce a graph at this time.</h3>");
}

if($isteacher)
    echo get_string('graphdesc', 'block_meter');

echo get_string('statdesc', 'block_meter');

echo $OUTPUT->footer();
