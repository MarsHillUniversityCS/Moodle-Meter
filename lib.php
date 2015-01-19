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
        $activities = $DB->get_records('logstore_standard_log', 
            array('courseid'=>$courseid, 'userid'=>$userid));
    } else {
        $sql = 'SELECT * FROM '.$CFG->prefix.'logstore_standard_log WHERE courseid='.$courseid.
            ' AND userid='.$userid.' AND timecreated BETWEEN '.$start.' AND '.$end;
        //echo $sql;
        $activities = $DB->get_records_sql($sql);
    }
    
    if(!$activities) {
        //error_log("no activities");
        return 0;
    }
    
    $score = 0;
    $conf = get_meter_config($courseid);
    foreach($activities as $activity){
        $score += get_row_score($activity, $conf);
    }

    return $score;
}

/**
* @param $activity is a single row from the mdl_logstore_standard_log table
*/
function get_row_score($activity, $config){


    $tier1      = $config['tier1_weight']; //assignments
    $tier2      = $config['tier2_weight']; //quizzes
    $tier3      = $config['tier3_weight']; //resources/pages/URLs/folders
    $tier4      = $config['tier4_weight']; //forums
    $tier5      = $config['tier5_weight']; //book/blog/wiki
    $tier6      = $config['tier6_weight']; //course views
    $default    = $config['default_weight'];

    $score      = 0;

    $eventname = $activity->eventname;

    if(preg_match('/mod_assign|assignsubmission/', $eventname)){
        $score += $tier1;
    } else if(preg_match('/mod_quiz/', $eventname)){
        $score += $tier2;
    } else if(preg_match('/mod_(page|url|folder)/', $eventname)){
        $score += $tier3;
    } else if(preg_match('/mod_forum/', $eventname)){
        $score += $tier4;
    } else if(preg_match('/mod_(book|blog|wiki)/', $eventname)){
        $score += $tier5;
    } else if(preg_match('/course_viewed/', $eventname)){ 
            $score += $tier6;
    } else{
        $score += $default;
    }


    return $score;
}

function set_job_flag($courseid){
    global $DB;
    $jobflag = new stdClass();
    $jobflag->courseid  = $courseid;
    $jobflag->name      = 'dostatsrun';
    $jobflag->value     = time();

    $jobflag->id        = $DB->insert_record('block_meter_config', $jobflag);

    if(!$jobflag->id) {
        error_log("Error setting do_stats_run started flag");
    }

    return $jobflag->id;
}

function remove_job_flag($courseid){
    global $DB;

    //remove the flag
    $DB->delete_records('block_meter_config', array('courseid'=>$courseid,
        'name'=>'dostatsrun'));

}

/**
    Deletes all of the config, stats, and studentstats for courseid
*/
function delete_all_course_data($courseid){

    global $DB;

    //Find all of the stats runs, and delete the associated
    //studentstats entries
    $statsruns = $DB->get_records('block_meter_stats',
        array('courseid'=>$courseid), '', 'id');
    
    if($statsruns){
        foreach ($statsruns as $stats){
            $DB->delete_records('block_meter_studentstats',
                array('statsid'=>$stats->id));
        }
    }
    
    //then delete the statsrun
    $DB->delete_records('block_meter_stats',
        array('courseid'=>$courseid));
    
    //delete the config associated with that course
    $DB->delete_records('block_meter_config',
        array('courseid'=>$courseid));

}

function do_cron_stats_run($courseid, $start = 0, $end = 0){
    global $CFG, $DB, $COURSE;

    if($start == 0 || $end == 0){ //if a defined term is not given

        //if no activity since the last run
        $lastactivity   = find_student_activity($courseid, false);
        $laststats      = find_last_stats_run($courseid);

        //don't go further, if there's no activity to process
        if($laststats > $lastactivity){
            error_log('No activity for course '.$courseid.'. Exiting');
            return;
        }
        
        $firstactivity   = find_student_activity($courseid);

        //what if we've skipped a previous day? Need to go back, process it and others
        //until we reach today.
        $now = time();
        //if this is true, there was no stats_run at all yesterday.
        //must be due to no activity
        if((strtotime('midnight', $now) - 86400) >= $laststats){
        
            //error_log('There has been more than a day since last stats');
            $end = strtotime('midnight tomorrow', $laststats);
            if($laststats == 0){
                $end = $now - 86401; //huh?
            }

            $format = '%m/%d/%y';
            while(($end + 86400) < $now){ 

                /*
                error_log('Running stats for start: '.userdate($firstactivity, $format).
                    ' to end: '.userdate($end, $format));
                */

                do_stats_run($courseid, $firstactivity, $end);

                $end = strtotime("+1 day", $end); 
            }


        } else { //normal stats run
            do_stats_run($courseid);
        }
    }
}

function do_stats_run($courseid, $start = 0, $end = 0){
    global $CFG, $DB;

    //WHY WOULD THIS HAPPEN? CAN IT HAPPEN?
    //What if this has already been run today? Do we care
    //about having multiple runs/day?
    //Should probably delete the other run on this day, and associated data,
    //and run this one. This ensures the X-Axis on the graph is correct later.
    //UPDATE: Not worrying about this currently - don't think it'll be a problem.


    //Add a flag, 'dostatsrun' to mdl_block_meter_config - remove it when this 
    //function is done.  If cron encounters a 'dostatsrun' flag, it would signify that a
    //previous run has been interrupted, and needs to re-do the stats for this course.
    set_job_flag($courseid);

    $statsrun = new stdClass();
    $statsrun->courseid = $courseid;
    $statsrun->mean = 0;
    $statsrun->stdv = 0;

    if($end == 0)
        $statsrun->statstime = time();
    else 
        $statsrun->statstime = $end;

   
    //Gives all students enrolled in this course
    //error_log("Course id is: ".$courseid);
    $context = context_course::instance($courseid);
    $students = get_enrolled_users($context, 'mod/assignment:submit', 0, 'u.id');

    if(!$students) {
        error_log("no students");
        remove_job_flag($courseid);
        return;
    }

    $statsid = $DB->insert_record('block_meter_stats', $statsrun);

    $studentstatslist = array();
    foreach($students as $student){
        $studentstats = new stdClass();
        $studentstats->studentid    = $student->id;
        $studentstats->statsid      = $statsid;
        $studentstats->score        = calculate_activity_score($student->id, 
                                        $courseid, $start, $end);
        $resultid = $DB->insert_record('block_meter_studentstats', $studentstats);
        if(!$resultid)
            error_log ('Failed to insert student stats for course '.
                $courseid.' and student '.$student->id);
        $studentstats->id = $resultid;
        $studentstatslist[] = $studentstats;
    }


    
    $sql = 'SELECT avg(score) avg, std(score) std  FROM '.
        $CFG->prefix.'block_meter_studentstats WHERE statsid='.$statsid;

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
        error_log('Unable to update block_meter_stats with mean and stdv.');

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

    remove_job_flag($courseid);

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
        'stats.statsid='.$stats->id.' ORDER BY zscore';

    $students = $DB->get_records_sql($sql);

    if(!$students) return array();
    
    foreach($students as $student){
        $student->level = get_activity_level($student->zscore);
    }
    return $students;
}

/**
* @return a student level given a user id and course id, null if student doesn't exist
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
     
    return get_activity_level($student->zscore);
}

function get_activity_level($zscore){

    //New way - level three is from -0.5 to 0.5, etc.
    if($zscore < -1.5)      $level = 1;
    else if ($zscore < -.5) $level = 2;
    else if ($zscore < .5)  $level = 3;
    else if ($zscore < 1.5) $level = 4;
    else                    $level = 5;

    return $level;
}


function get_graph_data($courseid){

    global $DB, $USER, $CFG;

    $sql = 'SELECT id, statstime from '.$CFG->prefix.
        'block_meter_stats WHERE courseid='.$courseid.' ORDER BY statstime ASC';
    $statsruns = $DB->get_records_sql($sql);
    
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

    $globalconf = get_global_config();

    //load defaults, if they don't exist?
    foreach(range(1,6) as $i){
        if(!isset($config['tier'.$i.'_weight']))
            $config['tier'.$i.'_weight'] = $globalconf['tier'.$i.'_weight'];
    }

    if(!isset($config['default_weight']))
        $config['default_weight'] = $globalconf['default_weight'];


    return $config;
}

function get_global_config(){
    global $DB;
    $config = array();
    $confs = $DB->get_records_select('config', 'name like \'block_meter%\'');

    foreach ($confs as $conf){
        $key = preg_replace('/block\_meter\_/', '', $conf->name);
        $config[$key] = $conf->value;
    }
    //error_log(print_r($config, true));
    return $config;
}


/**
* Return the time of the first (if $asc==true) or the last (if $asc==false)
* student access to this course
*/
function find_student_activity($courseid, $asc=true){
    global $DB, $CFG;

    $context = context_course::instance($courseid);
    $studentids = get_enrolled_users($context, 'mod/assignment:submit',
        0, 'u.id');

    if(!$studentids) return 0;

    if($asc){
        $sql = 'SELECT timecreated FROM '.$CFG->prefix.'logstore_standard_log WHERE courseid='.$courseid.
            ' AND userid IN ('.implode(',', array_keys($studentids)).')'.
            ' ORDER BY timecreated ASC LIMIT 1';
    } else {
        $sql = 'SELECT timecreated FROM '.$CFG->prefix.'logstore_standard_log WHERE courseid='.$courseid.
            ' AND userid IN ('.implode(',', array_keys($studentids)).')'.
            ' ORDER BY timecreated DESC LIMIT 1';
    }

    error_log($sql);

    $access = $DB->get_record_sql($sql);
    
    if(!$access){
        return 0; //nothing to process
    }
    
    return $access->time;

}


/**
* return the epoch time of the last stats run
* if no stats run yet, return 0
*/
function find_last_stats_run($courseid){

    global $DB, $CFG;

    $sql = 'SELECT statstime FROM '.$CFG->prefix.'block_meter_stats WHERE courseid='.$courseid.
    ' ORDER BY statstime DESC LIMIT 1';

    $access = $DB->get_record_sql($sql);
    
    if(!$access){
        return 0; //nothing to process
    }
    
    return $access->statstime;
}


/**
* Processes all data from $start - $final, processing one day at a time
*/
function load_historical_data($courseid, $start = 0, $final = 0, $deleteprev = false){
    global $CFG, $DB; 
    if($deleteprev){
        //what if the data isn't there?

        $stats = $DB->get_records('block_meter_stats', array('courseid'=>$courseid),'','id');
        if($stats){ // has some data
            $sel = 'statsid in ('.  implode(',', array_keys($stats)).')';

            $DB->delete_records_select('block_meter_studentstats', $sel);
            $DB->delete_records('block_meter_stats', array('courseid'=>$courseid));
        }
    }


    //they didn't provide a startime. Use the first log entry time - last log entry time
    if($start == 0 || $start > time()) { 

        $first = find_student_activity($courseid);
        if($first == 0){
            return; //nothing to process
        }

        $start = strtotime("midnight", $first);
        $end = strtotime("+1 day", $start);

    }

    //they didn't say when to stop - stop last day of student activity
    if($final == 0 || $start > $final){

        $last = find_student_activity($courseid, false);

        if($last == 0){
            return; //nothing to process
        }

        $final = $last;
        if($start >= $final){
            $final = strtotime("midnight tomorrow", $start);
        }
    }


    if($final < $end) $final = $end + 1;
    while($end < $final){ 
        do_stats_run($courseid, $start, $end); 
    
        $end = strtotime("+1 day", $end); 

        //debug only
        /*
        $format = '%m/%d/%y';
        error_log( "Processed from ".
            userdate($start, $format).' to '.userdate($end, $format)."\n");
        */
    }
}


