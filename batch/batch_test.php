<?php 

define('CLI_SCRIPT', true);
require_once('../../../config.php');
require_once('../lib.php');


global $DB, $USER, $CFG;

$courseid = 7;
$sql = 'SELECT id, statstime from '.$CFG->prefix.
    'block_meter_stats WHERE courseid='.$courseid.' ORDER BY statstime ASC';
$statsruns = $DB->get_records_sql($sql);

//print_r(array_keys($statsruns));
$statsidlist =  implode(',', array_keys($statsruns));


$sql = 'SELECT id,studentid,zscore FROM '.$CFG->prefix.'block_meter_studentstats WHERE
    statsid in ('.$statsidlist.')';

echo $sql;

$statentries = $DB->get_records_sql($sql);

if(!$statentries){
    echo "No students found. Exiting";
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


print_r($datalist);
