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
 * Form for editing Meter block instances.
 *
 * @package    Block
 * @subpackage Meter
 * @copyright  2014 Carter Benge, Marty Gilbert
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_meter_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $CFG, $DB, $USER, $COURSE;

        // Fields for editing block contents.
        $mform->addElement('header', 'configheader',
            get_string('configheader','block_meter'));

        $globalconf = get_global_config();
        foreach(range(1,6) as $i){

            $mform->addElement('text','config_tier'.$i.'_weight',
                '<b>'.get_string('tier'.$i,'block_meter').'</b>'.
                '<br />'.get_string('tier'.$i.'desc', 'block_meter'));

            $mform->setType('config_tier'.$i.'_weight', PARAM_INT);
            $mform->setDefault('config_tier'.$i.'_weight', 
                $globalconf['tier'.$i.'_weight']);

        }
            
        $mform->addElement('text','config_default_weight',
            get_string('defaultweight','block_meter'));

        $mform->setType('config_default_weight', PARAM_INT);
        $mform->setDefault('config_default_weight', 
            $globalconf['default_weight']);

        $mform->addElement('html', 
            '<span style="color: red;">'.get_string('configchanged',
            'block_meter').'</span>');

        
    }

    function validation ($data, $files){
        $errors = array();

        return $errors;
    }

}
