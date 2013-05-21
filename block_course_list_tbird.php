<?php

include_once($CFG->dirroot . '/course/lib.php');

//this table is defined in blocks/course_meeting_info/db/install.xml file.
//it is used by this block to show course meeting data
//the data is loaded from IMS xml files processed by the enrolment module 'imsenterprisetbird'
if (!defined("TBIRD_COURSE_INFO_TABLE")) { define("TBIRD_COURSE_INFO_TABLE", 'tbird_course_info'); }
//this tables is loaded (and defined) by the enrollment module as well.
if (!defined("TBIRD_COURSE_AUTOHIDE_TABLE")) { define("TBIRD_COURSE_AUTOHIDE_TABLE", 'tbird_course_autohide'); }
//database interface is defined in /lib/dml/moodle_database.php

class block_course_list_tbird extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_course_list_tbird');
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $icon  = '<img src="' . $OUTPUT->pix_url('i/course') . '" class="icon" alt="" />&nbsp;';

        $adminseesall = true;
        if (isset($CFG->block_course_list_tbird_adminview)) {
           if ( $CFG->block_course_list_tbird_adminview == 'own'){
               $adminseesall = false;
           }
        }

        if (empty($CFG->disablemycourses) and isloggedin() and !isguestuser() and
          !(has_capability('moodle/course:update', context_system::instance()) and $adminseesall)) {    // Just print My Courses
			//sort order may depend on startdate of course
            $sortorder = '';
            if(!empty($CFG->block_course_list_tbird_sortbystartdate)) {
            	$sortorder = 'startdate ASC, ';
            }
            if ($courses = enrol_get_my_courses(NULL, "visible DESC, $sortorder fullname ASC")) {
                foreach ($courses as $course) {
                	global $DB;
                	$coursecontext = context_course::instance($course->id);
                    $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                    $coursedata = "<a $linkcss title=\"" . format_string($course->shortname, true, array('context' => $coursecontext)) . "\" ".
                               "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">".$icon.format_string($course->fullname). "</a>";
                    //see if we can find course meeting information
	        		$select = "courseid = $course->id and name = 'meeting-info'";
					if($meeting = $DB->get_record_select(TBIRD_COURSE_INFO_TABLE,$select)) {
						//meeting data found, add to course info
						$coursedata .= '<br />' . $meeting->value;
					}
					//add start and end dates
					if(empty($CFG->block_course_list_tbird_onelinedates)) {
						//only show dates if start date is known...
						if($course->startdate > 0) {
							$coursedata .= '<br />' . get_string('startdate','block_course_list_tbird') .
								date(empty($CFG->block_course_list_tbird_startdateformat) ? 'M j, Y' : $CFG->block_course_list_tbird_startdateformat, $course->startdate);
							//get end date from our own internal table.
							$conditions = array('courseid' => $COURSE->id);
							//$fields = '*';
							//$strictness = IGNORE_MISSING;		 
							//if($endrec = $DB->get_record(TBIRD_COURSE_AUTOHIDE_TABLE,$conditions,$fields,$strictness)) {
							if($endrec = $DB->get_record(TBIRD_COURSE_AUTOHIDE_TABLE,$conditions)) {
								$coursedata .= '<br />' . get_string('enddate','block_course_list_tbird') .
									date(empty($CFG->block_course_list_tbird_enddateformat) ? 'M j, Y' : $CFG->block_course_list_tbird_enddateformat, $endrec->enddate);	
							}
						}
					} else {
						//use a single line for dates. only show if startdate known
						if($course->startdate > 0) {
							$coursedata .= '<br />' . get_string('dates','block_course_list_tbird') . date(empty($CFG->block_course_list_tbird_startdateformat) ? 'M j, Y' : $CFG->block_course_list_tbird_startdateformat, $course->startdate);
							//get end date from our own internal table.
							$conditions = array('courseid' => $COURSE->id);
							//$fields = '*';
							//$strictness = IGNORE_MISSING;		 
							//if($endrec = $DB->get_record(TBIRD_COURSE_AUTOHIDE_TABLE,$conditions,$fields,$strictness)) {
							if($endrec = $DB->get_record(TBIRD_COURSE_AUTOHIDE_TABLE,$conditions)) {
								$coursedata .= ' - ' . date(empty($CFG->block_course_list_tbird_enddateformat) ? 'M j, Y' : $CFG->block_course_list_tbird_enddateformat, $endrec->enddate);	
							}
						}
					}
					$this->content->items[]=$coursedata;
                }
                $this->title = get_string('mycourses');
            /// If we can update any course of the view all isn't hidden, show the view all courses link
                if (has_capability('moodle/course:update', context_system::instance()) || empty($CFG->block_course_list_tbird_hideallcourseslink)) {
                    $this->content->footer = "<a href=\"$CFG->wwwroot/course/index.php\">".get_string("fulllistofcourses")."</a> ...";
                }
            }
            $this->get_remote_courses();
            if ($this->content->items) { // make sure we don't return an empty list
                return $this->content;
            }
        }

        $categories = get_categories("0");  // Parent = 0   ie top-level categories only
        if ($categories) {   //Check we have categories
            if (count($categories) > 1 || (count($categories) == 1 && $DB->count_records('course') > 200)) {     // Just print top level category links
                foreach ($categories as $category) {
                	$categoryname = format_string($category->name, true, array('context' => context_coursecat::instance($category->id)));
                    $linkcss = $category->visible ? "" : " class=\"dimmed\" ";
                    $this->content->items[]="<a $linkcss href=\"$CFG->wwwroot/course/category.php?id=$category->id\">".$icon . $categoryname . "</a>";
                }
            /// If we can update any course of the view all isn't hidden, show the view all courses link
                if (has_capability('moodle/course:update', context_system::instance()) || empty($CFG->block_course_list_tbird_hideallcourseslink)) {
                    $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">".get_string('fulllistofcourses').'</a> ...';
                }
                $this->title = get_string('categories');
            } else {                          // Just print course names of single category
                $category = array_shift($categories);
                $courses = get_courses($category->id);

                if ($courses) {
                    foreach ($courses as $course) {
                    	$coursecontext = context_course::instance($course->id);
                        $linkcss = $course->visible ? "" : " class=\"dimmed\" ";

                        $this->content->items[]="<a $linkcss title=\""
                                   . format_string($course->shortname, true, array('context' => $coursecontext))."\" ".
                                   "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">"
                                   .$icon. format_string($course->fullname, true, array('context' => context_course::instance($course->id))) . "</a>";
                    }
                /// If we can update any course of the view all isn't hidden, show the view all courses link
                    if (has_capability('moodle/course:update', context_system::instance()) || empty($CFG->block_course_list_tbird_hideallcourseslink)) {
                        $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">".get_string('fulllistofcourses').'</a> ...';
                    }
                    $this->get_remote_courses();
                } else {

                    $this->content->icons[] = '';
                    $this->content->items[] = get_string('nocoursesyet');
                    if (has_capability('moodle/course:create', context_coursecat::instance($category->id))) {
                        $this->content->footer = '<a href="'.$CFG->wwwroot.'/course/edit.php?category='.$category->id.'">'.get_string("addnewcourse").'</a> ...';
                    }
                    $this->get_remote_courses();
                }
                $this->title = get_string('courses');
            }
        }

        return $this->content;
    }

    function get_remote_courses() {
        global $CFG, $USER, $OUTPUT;

        if (!is_enabled_auth('mnet')) {
            // no need to query anything remote related
            return;
        }

        $icon = '<img src="'.$OUTPUT->pix_url('i/mnethost') . '" class="icon" alt="" />&nbsp;';

        // shortcut - the rest is only for logged in users!
        if (!isloggedin() || isguestuser()) {
            return false;
        }

        if ($courses = get_my_remotecourses()) {
            $this->content->items[] = get_string('remotecourses','mnet');
            $this->content->icons[] = '';
            foreach ($courses as $course) {
            	$coursecontext = context_course::instance($course->id);
                $this->content->items[]="<a title=\"" . format_string($course->shortname, true, array('context' => $coursecontext)) . "\" ".
                    "href=\"{$CFG->wwwroot}/auth/mnet/jump.php?hostid={$course->hostid}&amp;wantsurl=/course/view.php?id={$course->remoteid}\">"
                    .$icon. format_string($course->fullname) . "</a>";
            }
            // if we listed courses, we are done
            return true;
        }

        if ($hosts = get_my_remotehosts()) {
            $this->content->items[] = get_string('remotehosts', 'mnet');
            $this->content->icons[] = '';
            foreach($USER->mnet_foreign_host_array as $somehost) {
                $this->content->items[] = $somehost['count'].get_string('courseson','mnet').'<a title="'.$somehost['name'].'" href="'.$somehost['url'].'">'.$icon.$somehost['name'].'</a>';
            }
            // if we listed hosts, done
            return true;
        }

        return false;
    }

}


