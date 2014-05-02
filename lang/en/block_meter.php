<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under the terms of the GNU
// General Public License as published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
// without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
// PURPOSE.  See the GNU General
// Public License for more details.
//
// You should have received a copy of the GNU General Public License along with Moodle.
// If not, see <http://www.gnu.org/licenses/>.

/** Strings for component 'block_meter', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package Block 
 * @subpackage Meter 
 * @copyright  2014 Carter Benge, Marty Gilbert
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Meter';
$string['blocktitle'] = 'Moodle Meter';

//Capabilities - descriptive names. Setup in db/access.php
$string['meter:addinstance'] = 'Add meter to course';

//config
$string['configheader']     = 'Set the default weights for each category of Moodle activity';
$string['tier1weight']      = 'Category 1 weight (assignment submissions/resource views)';
$string['tier2weight']      = 'Category 2 weight (other assignment activity)';
$string['tier3weight']      = 'Category 3 weight (quiz activity)';
$string['tier4weight']      = 'Category 4 weight (??)';
$string['tier5weight']      = 'Category 5 weight (blog or book module activity)';
$string['tier6weight']      = 'Category 6 weight (??)';
$string['defaultweight']    = 'Weight for all other activities';
$string['noconfigpresent']  = 'Please configure the Meter block before use';

//Graph
$string['viewgraph']    = 'View your activity graph';
$string['viewallusers'] = 'View the activity graph of all users';
$string['graphdesc']    =   
                            'The graph shows a user\'s activity level over time.'.
                            '<ul>'.
                            '<li>The area between -3 and -2 represents a Level 1 user.'.
                            ' A Level 1 user has significantly less Moodle interactivity than '.
                            'that of their peers in this course.</li>'.
                            '<li>The area between -2 and -1 represents a Level 2 user.'.
                            ' A Level 2 user has slightly less Moodle interactivity than '.
                            'that of their peers in this course.</li>'.
                            '<li>The area between -1 and 1 represents a Level 3 user.'.
                            ' A Level 3 user has an average amount of Moodle interactivity compared '.
                            'to that of their peers in this course.</li>'.
                            '<li>The area between 1 and 2 represents a Level 4 user.'.
                            ' A Level 4 user has slightly more Moodle interactivity than '.
                            'that of their peers in this course.</li>'.
                            '<li>The area between 2 and 3 represents a Level 5 user.'.
                            ' A Level 5 user has significantly more Moodle interactivity than '.
                            'that of their peers in this course.</li>'.
                            '</ul>';

//Levels
$string['level1user'] = 'Your Moodle usage is significantly less than that of your peers'.
                        ' in this course.';
$string['level2user'] = 'Your Moodle usage is slightly less than that of your peers'.
                        ' in this course.';
$string['level3user'] = 'Your Moodle usage is about average compared to that of your peers'.
                        ' in this course.';
$string['level4user'] = 'Your Moodle usage is somewhat more than that of your peers'.
                        ' in this course.';
$string['level5user'] = 'Your Moodle usage is significantly more than that of your peers'.
                        ' in this course.';


