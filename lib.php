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

function calculate_activity_score($userid, $courseid, $start = 0, $end = 0){
    global $CFG, $DB;

    if($end < $start) return 0;

    if($start == 0 || $end == 0){
        $activities = $DB->get_records('log', array('course'=>$courseid, 'userid'=>$userid));
    } else {
        $sql = 'SELECT * FROM '.$CFG->prefix.'log WHERE course='.$courseid.

            ' AND userid='.$userid.' AND time BETWEEN '.$start.' AND '.$end;
        //echo $sql;
        $activities = $DB->get_records_sql($sql);
    }
    
    if(!$activities) 
        return 0;
    
    $score = 0;
    $conf = get_meter_config($courseid);
    foreach($activities as $activity){
        $score += get_row_score($activity, $conf);
    }

    return $score;
}

/**
* @param $activity is a single row from the mdl_log table
*/
function get_row_score($activity, $config){


    //what if activity or config are bad?
    //do something.
    $tier1      = $config['tier1_weight'];
    $tier2      = $config['tier2_weight'];
    $tier3      = $config['tier3_weight'];
    $tier4      = $config['tier4_weight']; //tier4 is not used. WHY? XXX TODO CARTER!!
    $tier5      = $config['tier5_weight'];
    $tier6      = $config['tier6_weight']; //tier6 is not used. WHY? XXX TODO CARTER!!ier
    $default    = $config['default_weight'];

    $score      = 0;
    if($activity->module=='assign'){
        if($activity->action=='submit' ||
           $activity->action=='submit for grading' ||
           $activity->action=='view'){
            $score += $tier1;
        } else {
            $score += $tier2; 
        } 
    } else if($activity->module=='blog' || $activity->module=='book'){
            $score += $tier5;
    } else if($activity->module=='quiz'){
            $score += $tier3;
    } else if($activity->module=='resource'){
            $score += $tier1;
    } else{
        $score += $default;
    }

    return $score;
}

function do_stats_run($courseid, $start = 0, $end = 0){
    global $CFG, $DB;
    
    $statsrun = new stdClass();
    $statsrun->courseid = $courseid;
    $statsrun->mean = 0;
    $statsrun->stdv = 0;
    if($end == 0)
        $statsrun->statstime = time();
    else 
        $statsrun->statstime = $end;

    $statsid = $DB->insert_record('block_meter_stats', $statsrun);
   
    //Gives all students enrolled in this course
    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    $students = get_enrolled_users($context, 'mod/assignment:submit', 0, 'u.id');

    if(!$students)
        return;

    $studentstatslist = array();
    foreach($students as $student){
        $studentstats = new stdClass();
        $studentstats->studentid    = $student->id;
        $studentstats->statsid      = $statsid;
        $studentstats->score        = calculate_activity_score($student->id, 
                                        $courseid, $start, $end);

        $resultid = $DB->insert_record('block_meter_studentstats', $studentstats);
        if(!$resultid)
            error_log ('Failed to intert student stats for course '.$courseid.' and student '.$student->id);
        $studentstats->id = $resultid;
        $studentstatslist[] = $studentstats;
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

    //set the zscore for each student, now that we have mean and stddev
    foreach($studentstatslist as $student){
        if($statsrun->stdv == 0) 
            $student->zscore = 0;
        else 
            $student->zscore = ($student->score - $statsrun->mean) / $statsrun->stdv;
        
        $result = $DB->update_record('block_meter_studentstats', $student);

        if(!$result){
            error_log("Error updating student w/id: ".$student->studentid);
        }
    }

    return;
}


/**
* @return an array of objects representing the students in this 
* course and their score (1-5)
*/
function get_all_student_stats($courseid){
    global $DB, $CFG;
    $sql = 'SELECT * FROM '.$CFG->prefix.'block_meter_stats WHERE courseid='.$courseid.
        ' ORDER BY statstime DESC LIMIT 1';

    $stats = $DB->get_record_sql($sql);

    if(!$stats) return;
    
    $sql = 'SELECT stats.*,student.firstname, student.lastname, student.id as userid FROM '.
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

/**
* @return a student level given a user id and course id 
*/
function get_student_stats($userid, $courseid){
    global $DB, $CFG;
    $sql = 'SELECT * FROM '.$CFG->prefix.'block_meter_stats WHERE courseid='.$courseid.
        ' ORDER BY statstime DESC LIMIT 1';

    $stats = $DB->get_record_sql($sql);

    if(!$stats) return;
    
    $sql = 'SELECT stats.* FROM '.
        $CFG->prefix.'block_meter_studentstats as stats'.
        ' WHERE '.$userid.'=stats.studentid AND '.
        'stats.statsid='.$stats->id;

    $student = $DB->get_record_sql($sql);

    if(!$student) return null;
    
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
    
    return $student->level;
}


function get_graph_data($courseid){

    global $DB, $USER, $CFG;

    //$sql = 'SELECT id, statstime from '.$CFG->prefix.
    //    'block_meter_stats WHERE courseid='.$courseid.' ORDER BY statstime ASC';
    $sql = 'SELECT id, statstime from '.$CFG->prefix.
        'block_meter_stats WHERE courseid='.$courseid.' ORDER BY statstime ASC';
    $statsruns = $DB->get_records_sql($sql);
    
    //print_r(array_keys($statsruns));
    $statsidlist =  implode(',', array_keys($statsruns));
     
    $sql = 'SELECT stats.id, stats.studentid, stats.zscore FROM '.
        $CFG->prefix.'block_meter_studentstats as stats WHERE '.
        'statsid in ('.$statsidlist.')';
    
    $statentries = $DB->get_records_sql($sql);
    
    if(!$statentries){
        print_error("No students found. Exiting");
        exit;
    }

    $datalist = array();
    
    foreach ($statentries as $stat){
        if(array_key_exists($stat->studentid, $datalist)){
            $datalist[$stat->studentid][] = $stat->zscore;
        } else {
            $datalist[$stat->studentid] = array($stat->zscore);
        }
        
    }

    return array($statsruns, $datalist);
}

/**
* XX TODO document this function
* @return an array of config items for this course;
*/
function get_meter_config($courseid){
    global $DB;
    $config = array();
    $confs = $DB->get_records('block_meter_config', array('courseid'=>$courseid));
    foreach ($confs as $conf){
        $key = preg_replace('/block\_meter\_/', '', $conf->name);
        $config[$key] = $conf->value;
    }

    return $config;
}


/**
* Processes all data from $start - $final, processing one day at a time
*/
function load_historical_data($courseid, $start = 0, $final = 0, $deleteprev = false){
    global $CFG, $DB; 

    if($deleteprev){
        $stats = $DB->get_records('block_meter_stats', array('courseid'=>$courseid),'','id');

        $sel = 'statsid in ('.  implode(',', array_keys($stats)).')';


        $DB->delete_records_select('block_meter_studentstats', $sel);
        $DB->delete_records('block_meter_stats', array('courseid'=>$courseid));
    }

    //they didn't provide a startime. Use the first log entry time - last log entry time
    if($start == 0 || $start > time()) { 

        $sql = 'SELECT time FROM '.$CFG->prefix.'log WHERE course='.$courseid.
            ' ORDER BY time ASC LIMIT 1';
        $first = $DB->get_record_sql($sql);

        $start = strtotime("midnight", $first->time);
        $end = strtotime("+1 day", $start);

    }

    //they didn't say when to stop - stop previous midnight
    if($final == 0 || $start > $final){
        $final = strtotime("midnight");

        if($start >= $final){
            $final = strtotime("midnight tomorrow", $start);
        }
    }

    while($end < $final){ //Dec 31 @ midnight (2013)
        do_stats_run($courseid, $start, $end); 
    
        $end = strtotime("+1 day", $end); //23:59:59

        //debug only
        $format = '%m/%d/%y';
        echo "Processed from ".userdate($start, $format).' to '.userdate($end, $format)."\n";
    }
}


