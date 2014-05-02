<?php 

define('CLI_SCRIPT', true);
require_once('../../../config.php');
require_once('../lib.php');


global $DB, $USER, $CFG;

$start = 1377316800; //august 24th @ midnight (2013)
$end = strtotime("+1 day", $start);

while($end < 1388466000){ //Dec 31 @ midnight (2013)
    //echo $start.' '.$end."\n";
    do_stats_run(7, $start, $end); 

    
    //$start = $end + 1; //00:00:00
    $end = strtotime("+1 day", $end); //23:59:59

    echo "Processed from ".$start.' to '.$end."\n";
}

