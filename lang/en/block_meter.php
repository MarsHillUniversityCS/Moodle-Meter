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
$string['tier1']        = 'Category 1 weight';
$string['tier1desc']    = '(assignment related activity)';
$string['tier2']        = 'Category 2 weight';
$string['tier2desc']    = '(quiz activity)';
$string['tier3']        = 'Category 3 weight';
$string['tier3desc']    = '(resource/page/URL/folder activity)';
$string['tier4']        = 'Category 4 weight';
$string['tier4desc']    = '(forum activity)';
$string['tier5']        = 'Category 5 weight';
$string['tier5desc']    = '(book/blog/wiki activity)';
$string['tier6']        = 'Category 6 weight';
$string['tier6desc']    = '(Course views)';
$string['defaultweight']        = 'Weight for all other activities';
$string['defaultweightdesc']    = 'Weight for any log activity for a course other than the above
listed categories.';

$string['configchanged']    = '**If you change the category weights, the statistics will
need to re-calculate with the new settings. This process may take several minutes.';
$string['noconfigpresent']  = 'Please configure the Meter block before use. <br />
                            To do this, simply:
                            <ol>
                                <li>Click "Turn editing on"</li>
                                <li>Click the settings (gear) icon</li>
                                <li>Click \'Save changes\'</li>
                            </ol>';

//admin config
$string['cronhour']     = 'Hour to calculate statistics'; 
$string['cronhourdesc'] = 'The hour when the activity meter statistics should be calculated. This is an intensive process, and should be set to a time when the server load is minimal.';


//Graph
$string['viewgraph']    = 'View your activity graph';
$string['viewallusers'] = 'View the activity graph of all users';
$string['graphdesc']    =   '<h3>Graph information</h3>
                            The graph shows a user\'s activity level over time.
                            <ul>
                            <li><b>Level 1</b> - The area between -3 and -1.5.
                             A Level 1 user has significantly less Moodle activity than 
                            that of their peers in this course.</li>
                            <li><b>Level 2</b> - The area between -1.5 and -0.5.
                             A Level 2 user has slightly less Moodle activity than 
                            that of their peers in this course.</li>
                            <li><b>Level 3</b> - The area between -0.5 and 0.5.
                             A Level 3 user has an average amount of Moodle activity compared 
                            to that of their peers in this course.</li>
                            <li><b>Level 4</b> - The area between 0.5 and 1.5.
                             A Level 4 user has slightly more Moodle activity than 
                            that of their peers in this course.</li>
                            <li><b>Level 5</b> - The area between 1.5 and 3.
                             A Level 5 user has significantly more Moodle activity than 
                            that of their peers in this course.</li>
                            </ul>';

$string['statdesc']   =     '<h3>Statistics information</h3>
                            <ul>
                            <li><b>Mean</b> - The mean is the average of the student activity
                            scores. This is cumulative, meaning that the calculations are
                            done from the first day of student activity to the day listed
                            on the X axis. <br />The mean is shown on the graph at y = 0. </li>'.
                            '<li><b>Standard deviation</b> - The standard deviation
                            is a measure of how spread out the scores are from the mean.
                            <br />
                            In a normal distribution,
                            <ul>
                                <li>Roughly 68% lie within 1 standard deviation</li>
                                <li>Roughly 95% lie within 2 standard deviations</li>
                                <li>Roughly 99% lie within 3 standard deviations</li>
                            </ul>
                            The standard deviations are shown on the Y axis. </li>
                            <li><b>Z-Score</b> - A student\'s z-score describes how many
                            standard deviations their activity score is above (or below, if it\'s
                            negative) the mean activity score.</li>
                            </ul>';

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


