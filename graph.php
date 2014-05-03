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
require_once($CFG->libdir.'/graphlib.php');
require_once('lib.php');

require_login();

$courseid = required_param('id', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

$context = get_context_instance(CONTEXT_COURSE, $courseid);

$isteacher = false;
if (has_capability('moodle/grade:viewall', $context)) { //teacher
    $isteacher = true;
}


if(!$isteacher && $userid == 0){
    print_error('improper permissions');
}

$chart = new graph(1324,768);
$chart->parameter['title'] = 'Moodle Meter Stats';
$chart->parameter['x_label'] = 'Date';
$chart->parameter['y_label_left'] = 'Z-Score';
    
$colorarray = array('blue', 'yellow', 'green', 'red', 'orange', 'maroon', 
    'purple', 'gray', 'ltblue', 'navy', 'olive', 'fuchsia', 'ltgreen');
    
    
/***********************************************
***********************************************/
$array3d =  get_graph_data($courseid);

$statsruns = $array3d[0]; //x-data times
//echo var_dump($statsruns);
$time = array();
foreach($statsruns as $statsrun){
    $statsrun->statstime;
    $time[] = userdate($statsrun->statstime, '%a %b %e, %y');
}
$chart->x_data = $time; //Set x-values with human readable times


$datalist = $array3d[1];  //y-data array of students and their z-scores


$studentlist = get_enrolled_users($context, 'mod/assignment:submit');
/**********************************************************************
**********************************************************************/

if($isteacher && $userid == 0){
        
    $j = 0;
    foreach ($datalist as $sid=>$data){
        $chart->y_data[$sid] = $data;
        $chart->y_format[$sid] = 
            array('colour' => $colorarray[$j],
            'line'   => 'brush',
            'legend' => $studentlist[$sid]->lastname.', '.
                $studentlist[$sid]->firstname);
        $j++;
    }

    $chart->y_order = array_keys($datalist);
} else{ //If a student views the graph or teacher views one student

    //echo var_dump($ydata);
    $chart->y_data[1] = $datalist[$userid];

    $chart->y_format[1] =
        array('colour' => $colorarray[0],
        'line'   => 'brush',
        'legend' => $studentlist[$userid]->lastname.', '.
            $studentlist[$userid]->firstname);

    $chart->y_order = array(1);
}
    

$chart->parameter['point_size']         = 6;
$chart->parameter['x_axis_angle']       = 60; // x_axis text rotation
$chart->parameter['x_label_angle']      = 0; // rotate y_label text. 
$chart->parameter['x_offset']           = 0; 
$chart->parameter['label_size']         = 12; 
$chart->parameter['label_colour']       = 'black'; 

$chart->parameter['brush_size']         = 1;
$chart->parameter['brush_type']         = 'square';
//$chart->parameter['shadow_offset']      = 4;
$chart->parameter['shadow']             = 'none';
$chart->parameter['bar_size']           = 30;

$chart->parameter['zero_axis']          = 'black';
$chart->parameter['inner_border_type']  = 'y-left'; // only draw left y axis as zero axis
//$chart->parameter['inner_background']   = 'gray';
$chart->parameter['outer_padding']      = 10;
$chart->parameter['x_inner_padding']    = 10;
$chart->parameter['y_inner_padding']    = 10;

$chart->parameter['y_decimal_left']     = 1;
$chart->parameter['y_axis_gridlines']   = 13;
$chart->parameter['y_axis_num_ticks']   = 6;
$chart->parameter['y_min_left']         = -3;
$chart->parameter['y_max_left']         = 3;

$chart->parameter['x_axis_text']        =  3; // print every other tick on x axis
$chart->parameter['axis_size']          =  8;
$chart->parameter['grid_colour']        =  'grayDD';
//$chart->parameter['y_axis_text_left'] =  2; // print every other tick on left y axis
//$chart->parameter['tick_length']      = -2; // tick is drawn pointing outside the plotting area
//$chart->parameter['x_ticks_colour']   = 'none'; // no x ticks (colour = 'none') 
$chart->parameter['x_axis_gridlines']   = 'auto'; // no x ticks (colour = 'none') 

$chart->parameter['legend']           = 'outside-top';
$chart->parameter['legend_border']    = 'black';

// draw it.
$chart->draw();

