<?php

include_once($CFG->dirroot . '/course/lib.php');

// this table is defined in db/install.xml and used by this block to show course meeting data.
// the data is loaded from IMS xml files processed by the enrolment module 'imsenterprisetbird'.
if (!defined("TBIRD_COURSE_INFO_TABLE")) { define("TBIRD_COURSE_INFO_TABLE", 'tbird_course_info'); }
// this tables is used as well, and currently still hand-created.
if (!defined("TBIRD_COURSE_AUTOHIDE_TABLE")) { define("TBIRD_COURSE_AUTOHIDE_TABLE", 'tbird_course_autohide'); }
// database interface is defined in /lib/dml/moodle_database.php

require_once($CFG->dirroot.'/blocks/course_list_tbird/lib.php');

class block_course_list_tbird extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_course_list_tbird');
    }

    function has_config() {
        return true;
    }

    // only show in courses and My Moodle page.
    function applicable_formats() {
    	return array(
            'course-view' => true,
            'my' => true,
            'site-index' => true	// front page.
    	);
    }
    
    function get_content() {
        global $CFG, $DB, $OUTPUT;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        // $this->content->items = array();
        // $this->content->icons = array();
        $this->content->footer = '';

        $icon  = '<img src="' . $OUTPUT->pix_url('i/course') . '" class="icon" alt="" />';

        $adminseesall = true;
        if (isset($CFG->block_course_list_tbird_adminview)) {
           if ( $CFG->block_course_list_tbird_adminview == 'own'){
               $adminseesall = false;
           }
        }
        
        $showcategories = $CFG->block_course_list_tbird_showcategory;
        $allcategories = array();
        if($showcategories) {
            // we need the YUI TreeView code for this.
            $this->page->requires->js('/blocks/course_list_tbird/module.js');
            // we now need to full list of categories.
            $allcategories = get_categories('none', NULL, false);
        }
        
        $categories = get_categories("0");  // Parent = 0   ie top-level categories only.
        
        if (empty($CFG->disablemycourses) and isloggedin() and !isguestuser() and
          !(has_capability('moodle/course:update', context_system::instance()) and $adminseesall)) {    // Just print My Courses
			// sort order may depend on startdate of course.
            $sortorder = '';
            if(!empty($CFG->block_course_list_tbird_sortbystartdate)) {
            	$sortorder = 'startdate ASC, ';
            }
            if ($courses = enrol_get_my_courses(NULL, "visible DESC, $sortorder fullname ASC")) {
                $catdata = array();
                if($showcategories)
                    $this->content->text = '<div id="categoryContainer">';  // for YUI2 TreeView.
                $this->content->text .= '<ul>';
                foreach ($courses as $course) {
                   	$coursecontext = context_course::instance($course->id);
                    $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                    // should we show start/end dates for this course?
                    $dateinfo = '';
                    // add start and end dates.
                    if (!$CFG->block_course_list_tbird_onelinedates) {
                        //only show dates if start date is known...
                        if ($course->startdate > 0) {
                            $dateinfo = get_string('startdate','block_course_list_tbird') .
                                date(empty($CFG->block_course_list_tbird_startdateformat) ? 'M j, Y' : $CFG->block_course_list_tbird_startdateformat, $course->startdate);
                            //get end date from our own internal table.
                            $conditions = array('courseid' => $course->id);
                            if ($endrec = $DB->get_record(TBIRD_COURSE_AUTOHIDE_TABLE,$conditions)) {
                                $dateinfo .= "\n" . get_string('enddate','block_course_list_tbird') .
                                    date(empty($CFG->block_course_list_tbird_enddateformat) ? 'M j, Y' : $CFG->block_course_list_tbird_enddateformat, $endrec->enddate);
                            }
                        }
                    } else {
                        // use a single line for dates. only show if startdate known.
                        if ($course->startdate > 0) {
                            $dateinfo = get_string('datesheader','block_course_list_tbird') . date(empty($CFG->block_course_list_tbird_startdateformat) ? 'M j, Y' : $CFG->block_course_list_tbird_startdateformat, $course->startdate);
                            //get end date from our own internal table.
                            $conditions = array('courseid' => $course->id);
                            if ($endrec = $DB->get_record(TBIRD_COURSE_AUTOHIDE_TABLE,$conditions)) {
                                $dateinfo .= ' - ' . date(empty($CFG->block_course_list_tbird_enddateformat) ? 'M j, Y' : $CFG->block_course_list_tbird_enddateformat, $endrec->enddate);
                            }
                        }
                    }
                    // do we have meeting info for this course?
                    $meetinginfo = '';
                    $select = "courseid = $course->id and name = 'meeting-info'";
   				    if ($meeting = $DB->get_record_select(TBIRD_COURSE_INFO_TABLE,$select)) {
   				        $meetinginfo = get_string('meetinginfoheader','block_course_list_tbird') . $meeting->value;
				    }                    

				    $coursedata = html_writer::start_tag('li');
                    if(!$showcategories)
                        $coursedata .= $icon;
                    $coursedata .= "<a $linkcss title=\"" . get_string('clickhere','block_course_list_tbird') .
                        ' ' . format_string($course->shortname, true, array('context' => $coursecontext));
                    if($showcategories) {   // add meeting and date info in hover-over text.
                        if($meetinginfo <> '')
                            $coursedata .= "\n" . $meetinginfo;
                        if($dateinfo <> '')
                            $coursedata .= "\n" . $dateinfo;
                    }
                        
                    $coursedata .= "\" href=\"$CFG->wwwroot/course/view.php?id=$course->id\">" . format_string($course->fullname) . '</a>';
                    // add meeting information.
                    if(!$showcategories) {
                        // add meeting and date infor below course link.
            		    if($meetinginfo <> '')
            		        $coursedata .= '<br>' . $meetinginfo;
    					// add start and end dates.
    					if($dateinfo <> '')
    					    $coursedata .= '<br>' . $dateinfo;
                    }
					//$coursedata .= html_writer::end_tag('a');
					$coursedata .= html_writer::end_tag('li');
					// if showing sorted by category, add to proper list.
					if ($showcategories) {
					    if($showcategories == SHOWCATEGORY_TOP) {
                           // to find top enclosing category, we can parse $cat->path.
					       $path = $allcategories[$course->category]->path;
					       // this is straight from Moodle 2.6+ code.
					       $parents = preg_split('|/|', $path, 0, PREG_SPLIT_NO_EMPTY);
					       array_pop($parents);
					       if (isset($parents[0]))
					           $catid = $parents[0];
					       else {
					           // this course is in a top-level category!
					           $catid = $course->category;
					       }
					    } else {
					        // show in course parent category.
					        $catid = $course->category;
					    }
					    // add to category course data.
					    // sort by $category->sortorder
					    $catsortorder = $allcategories[$catid]->sortorder;
					    if (isset($catdata[$catsortorder])) {
					       // add course data in this category
					       $catdata[$catsortorder]->coursedata .= $coursedata;
					    } else {
					        // first entry.
					        $d = new stdClass();
					        $d->catid = $catid;
					        $d->coursedata = $coursedata;
					       $catdata[$catsortorder] = $d;
					    }
					} else {
					    // just list courses.
					    $this->content->text .= $coursedata;
					}
                }
                $this->title = get_string('mycourses');
            /// If we can update any course of the view all isn't hidden, show the view all courses link
                if (has_capability('moodle/course:update', context_system::instance()) || empty($CFG->block_course_list_tbird_hideallcourseslink)) {
                    $this->content->footer = "<a href=\"$CFG->wwwroot/course/index.php\">".get_string("fulllistofcourses")."</a> ...";
                }
                if ($showcategories) {
                    // sort by the global category sort order.
                    ksort($catdata);
                    foreach($catdata as $catorder => $catinfo) {
                        $this->content->text .= html_writer::start_tag('li');
                        $this->content->text .= html_writer::start_tag('em') . $allcategories[$catinfo->catid]->name . html_writer::end_tag('em');
                        $this->content->text .= html_writer::start_tag('ul') . $catinfo->coursedata . html_writer::end_tag('ul');
                        $this->content->text .= html_writer::end_tag('li');
                    }
                }
                $this->content->text .= html_writer::end_tag('ul');
                if($showcategories)
                    $this->content->text .= html_writer::end_tag('div');
            }
            //$this->get_remote_courses();
            //if ($this->content->items) { // make sure we don't return an empty list
            //    return $this->content;
            //}
            if ($this->content->text <> '') { // make sure we don't return empty content
                return $this->content;
            }
        }

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
