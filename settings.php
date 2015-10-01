<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $options = array();
    for ($i = 0; $i < 24; $i++) {
        $options[$i] = sprintf("%02d",$i);
    }

    foreach(range(1,6) as $i){
        $settings->add(
            new admin_setting_configtext('block_meter_tier'.$i.'_weight', 
            get_string('tier'.$i, 'block_meter'),
            get_string('tier'.$i.'desc', 'block_meter'), 35-(($i-1)*5), PARAM_INT));
    }

    $settings->add(
        new admin_setting_configtext('block_meter_default_weight',
        get_string('defaultweight', 'block_meter'), 
        get_string('defaultweightdesc', 'block_meter'), 5, PARAM_INT));
    

}
