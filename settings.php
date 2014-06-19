<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/course_list_tbird/lib.php');

if ($ADMIN->fulltree) {
    $options = array('all'=>get_string('allcourses', 'block_course_list_tbird'), 'own'=>get_string('owncourses', 'block_course_list_tbird'));

    $settings->add(new admin_setting_configselect('block_course_list_tbird_adminview', get_string('adminview', 'block_course_list_tbird'),
                       get_string('configadminview', 'block_course_list_tbird'), 'all', $options));
    
    $settings->add(new admin_setting_configselect('block_course_list_tbird_showcategory', get_string('showcategory', 'block_course_list_tbird'),
                       get_string('configshowcategorydescr', 'block_course_list_tbird'), 
            0, array(   0 => get_string('showcategoryno', 'block_course_list_tbird'),
                        1 => get_string('showcategoryparent', 'block_course_list_tbird'),
                        2 => get_string('showcategorytop', 'block_course_list_tbird'))
    ));
	
    $settings->add(new admin_setting_configcheckbox('block_course_list_tbird_hideallcourseslink', get_string('hideallcourseslink', 'block_course_list_tbird'),
                       get_string('confighideallcourseslink', 'block_course_list_tbird'), 0));
	
    $settings->add(new admin_setting_configcheckbox('block_course_list_tbird_sortbystartdate', get_string('sortbystartdate', 'block_course_list_tbird'),
                   get_string('configsortbystartdate', 'block_course_list_tbird'), 0));
                   
	$settings->add(new admin_setting_configtext('block_course_list_tbird_startdateformat', get_string('startdateformat', 'block_course_list_tbird'),
				get_string('configstartdateformatdescr', 'block_course_list_tbird'),
				'M j, Y', PARAM_RAW, 30 ));

	$settings->add(new admin_setting_configtext('block_course_list_tbird_enddateformat', get_string('enddateformat', 'block_course_list_tbird'),
				get_string('configenddateformatdescr', 'block_course_list_tbird'),
				'M j, Y', PARAM_RAW, 30 ));
				
	$settings->add(new admin_setting_configcheckbox('block_course_list_tbird_onelinedates', get_string('onelinedates', 'block_course_list_tbird'),
                   get_string('configonelinedates', 'block_course_list_tbird'), 0));
}


