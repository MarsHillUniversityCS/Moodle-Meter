<?php 

define('CLI_SCRIPT', true);
require_once('../../../config.php');
require_once('../lib.php');


/**

This batch script will reset *all* courses, and re-calculate the historical stats.

Run this at a time of low system load; it'll cause the server to crunch
numbers for a good while.

*/


$metercourses = $DB->get_records('block_meter_stats', null, '', 'courseid');

echo 'Processing '.sizeof($metercourses).' courses'."\n";

if(!$metercourses) exit;

foreach ($metercourses as $course){
    //echo $course->courseid."\n";
    load_historical_data($course->courseid, 0, 0, true);
}
