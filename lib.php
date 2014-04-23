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
 * @package    Meter
 * @copyright  2014 Carter Benge, Marty Gilbert
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

defined('MOODLE_INTERNAL') || die();

function calculate_activity_score($userid, $courseid){
    global $CFG, $DB;

    $activities = $DB->get_records('log', array('course'=>$courseid, 'userid'=>$userid));
    
    if(!$activities) 
        return 0;
    
    $score = 0;
    foreach($activities as $activity){
        $score += get_row_score($activity);
    }

    return $score;
}

/**
* @param $activity is a single row from the mdl_log table
*/
function get_row_score($activity){

    $score      = 0;
    $hightier1  = 36;
    $tier1      = 30;
    $hightier2  = 26;
    $tier2      = 20;
    $hightier3  = 16;
    $tier3      = 10;
    $default    = 1;

    if($activity->module=='assign'){
        if($activity->action=='submit' ||
           $activity->action=='submit for grading' ||
           $activity->action=='view'){
            $score += $hightier1;
        } else {
            $score += $tier1; 
        } 
    } else if($activity->module=='blog' || $activity->module=='book'){
            $score += $hightier3;
    } else if($activity->module=='quiz'){
            $score += $hightier2;
    } else if($activity->module=='resource'){
            $score += $hightier1;
    } else{
        $score += $default;
    }
    return $score;
}

function do_stats_run($courseid){
    global $CFG, $DB;
    
    $statsrun = new stdClass();
    $statsrun->courseid = $courseid;
    $statsrun->mean = 0;
    $statsrun->stdv = 0;
    $statsrun->statstime = time();

    $statsid = $DB->insert_record('block_meter_stats', $statsrun);
   
    //Gives all students enrolled in this course
    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    $students = get_enrolled_users($context, 'mod/assignment:submit', 0, 'u.id');

    if(!$students)
        return;

    foreach($students as $student){
        $studentstats = new stdClass();
        $studentstats->studentid    = $student->id;
        $studentstats->statsid      = $statsid;
        $studentstats->score        = calculate_activity_score($student->id, $courseid);

        $resultid = $DB->insert_record('block_meter_studentstats', $studentstats);
        if(!$resultid)
            error_log ('Failed to intert student stats for course '.$courseid.' and student '.$student->id);
    }
    
    $sql = 'SELECT avg(score) avg, std(score) std  FROM '.$CFG->prefix.'block_meter_studentstats WHERE statsid='.$statsid;

    $math = $DB->get_record_sql($sql);
    if(!$math){
        error_log('Unable to calculate avg and std.');
        error_log($sql);
        return;
    }
    
    $statsrun->id = $statsid;
    $statsrun->mean = $math->avg;
    $statsrun->stdv = $math->std;

    $resultid = $DB->update_record('block_meter_stats', $statsrun);
    if(!$resultid)
        error_log('Unable to update block_meterstats with mean and stdv.');

    return;
}


/**
* @return an array of objects representing the students in this 
* course and their score (1-5)
*/
function get_student_stats($courseid){
    global $DB, $CFG;
    $sql = 'SELECT * FROM '.$CFG->prefix.'block_meter_stats WHERE courseid='.$courseid.
        ' ORDER BY statstime DESC LIMIT 1';

    $stats = $DB->get_record_sql($sql);

    if(!$stats) return;
    
    $sql = 'SELECT stats.*,student.firstname, student.lastname FROM '.
        $CFG->prefix.'block_meter_studentstats as stats, '.
        $CFG->prefix.'user as student WHERE student.id=stats.studentid AND '.
        'stats.statsid='.$stats->id.' ORDER BY lastname,firstname';

    $students = $DB->get_records_sql($sql);

    if(!$students) return array();
    
    foreach($students as $student){
            $level1 = 0;
            $level2 = $stats->mean - (2 * $stats->stdv);
            $level3 = $stats->mean - (1 * $stats->stdv);
            $level4 = $stats->mean + (1 * $stats->stdv);
            $level5 = $stats->mean + (2 * $stats->stdv);

            if($student->score < $level2){
                $student->level = 1;
            } else if ($student->score < $level3){
                $student->level = 2;
            } else if ($student->score < $level4){
                $student->level = 3;
            } else if ($student->score < $level5){
                $student->level = 4;
            } else {
                $student->level = 5;
            }
    }
    return $students;
}

