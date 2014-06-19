<?php

include_once($CFG->dirroot . '/course/lib.php');
include_once($CFG->libdir . '/coursecatlib.php');

//this table is defined in blocks/course_meeting_info/db/install.xml file.
//it is used by this block to show course meeting data
//the data is loaded from IMS xml files processed by the enrolment module 'imsenterprisetbird'
if (!defined("TBIRD_COURSE_INFO_TABLE")) { define("TBIRD_COURSE_INFO_TABLE", 'tbird_course_info'); }
//this tables is loaded (and defined) by the enrollment module as well.
if (!defined("TBIRD_COURSE_AUTOHIDE_TABLE")) { define("TBIRD_COURSE_AUTOHIDE_TABLE", 'tbird_course_autohide'); }
//database interface is defined in /lib/dml/moodle_database.php

require_once($CFG->dirroot.'/blocks/course_list_tbird/lib.php');

class block_course_list_tbird extends block_base {
    
    function init() {
        $this->title = get_string('pluginname', 'block_course_list_tbird');
    }

    function has_config() {
        return true;
    }

    //only show in courses and My Moodle page
    function applicable_formats() {
    	return array(
                'course-view' => true,
                'my' => true,
                'site-index' => true	//front page
    	    	);
    }

    function get_content() {
        global $CFG, $DB, $OUTPUT;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        //$this->content->items = array();
        //$this->content->icons = array();
        $this->content->footer = '';

        $this->page->requires->js('/blocks/course_list_tbird/module.js');
        
        $icon  = '<img src="' . $OUTPUT->pix_url('i/course') . '" class="icon" alt="" />';

        $adminseesall = true;
        if (isset($CFG->block_course_list_tbird_adminview)) {
           if ($CFG->block_course_list_tbird_adminview == 'own') {
               $adminseesall = false;
           }
        }

        $showcategories = $CFG->block_course_list_tbird_showcategory;
        $categories = coursecat::get(0)->get_children();  // Parent = 0   ie top-level categories only
        
        if (empty($CFG->disablemycourses) and isloggedin() and !isguestuser() and
          !(has_capability('moodle/course:update', context_system::instance()) and $adminseesall)) {    // Just print My Courses
			// sort order may depend on startdate of course.
            $sortorder = '';
            if (!empty($CFG->block_course_list_tbird_sortbystartdate)) {
            	$sortorder = 'startdate ASC,';
            }
            if ($courses = enrol_get_my_courses(NULL, "visible DESC, $sortorder fullname ASC")) {
                $catdata = array();
                //$this->content->text = '<ul class="unlist">';
                if($showcategories)
                    $this->content->text = '<div id="categoryContainer">';  // for YUI2 TreeView.
                $this->content->text .= '<ul>';
                foreach ($courses as $course) {
                	global $DB;
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
                            //$fields = '*';
                            //$strictness = IGNORE_MISSING;
                            //if ($endrec = $DB->get_record(TBIRD_COURSE_AUTOHIDE_TABLE,$conditions,$fields,$strictness)) {
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
                    //$coursedata = '';
                    //if ($showcategories)
                    //    $coursedata = '<li>';
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
					    // we can use coursecat::get($catid) to get the category, and then parse $cat->path
					    // or this:
					    if($showcategories == SHOWCATEGORY_TOP) {
					       $parents = coursecat::get($course->category)->get_parents();
					       if (isset($parents[0]))
					           $level = $parents[0];
					       else {
					           // this course is in a top-level category!
					           $level = $course->category;
					       }
					    } else {
					        // show in course parent category.
					        $level = $course->category;
					    }
					    // add to category course data.
					    if (isset($catdata[$level]))
					       $catdata[$level] .= $coursedata;
					    else
					        // first entry.
					       $catdata[$level] = $coursedata;
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
                    $firstcat = true;
                    foreach($catdata as $catid => $catinfo) {
                        $this->content->text .= html_writer::start_tag('li');
                        $this->content->text .= html_writer::start_tag('em') . coursecat::get($catid)->get_formatted_name() . html_writer::end_tag('em');
                        $this->content->text .= html_writer::start_tag('ul') . $catinfo . html_writer::end_tag('ul');
                        $this->content->text .= html_writer::end_tag('li');
                    }
                }
                $this->content->text .= html_writer::end_tag('ul');
                if($showcategories)
                    $this->content->text .= html_writer::end_tag('div');
            }
            
            if ($this->content->text <> '') { // make sure we don't return empty content
                return $this->content;
          }
        }

        if ($categories) {   //Check we have categories
            if (count($categories) > 1 || (count($categories) == 1 && $DB->count_records('course') > 200)) {     // Just print top level category links
                $this->content->text = '<ul>';
                foreach ($categories as $category) {
                    $categoryname = $category->get_formatted_name();
                    $linkcss = $category->visible ? "" : " class=\"dimmed\" ";
                    $this->content->text .= "<li><a $linkcss href=\"$CFG->wwwroot/course/index.php?categoryid=$category->id\">".$icon . $categoryname . "</a></li>";
                }
                $this->content->text .= '</ul>';
                /// If we can update any course of the view all isn't hidden, show the view all courses link
                if (has_capability('moodle/course:update', context_system::instance()) || empty($CFG->block_course_list_tbird_hideallcourseslink)) {
                    $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">".get_string('fulllistofcourses').'</a> ...';
                }
                $this->title = get_string('categories');
            } else {                          // Just print course names of single category
                $category = array_shift($categories);
                $courses = get_courses($category->id);

                if ($courses) {
                    $this->content->text = '<ul>';
                    foreach ($courses as $course) {
                        $coursecontext = context_course::instance($course->id);
                        $linkcss = $course->visible ? "" : " class=\"dimmed\" ";

                        $this->content->text.="<li><a $linkcss title=\""
                                   . format_string($course->shortname, true, array('context' => $coursecontext))."\" ".
                                   "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">"
                                   .$icon. format_string($course->fullname, true, array('context' => context_course::instance($course->id))) . "</a></li>";
                    }
                    $this->content->text = '</ul>';
                    /// If we can update any course of the view all isn't hidden, show the view all courses link
                    if (has_capability('moodle/course:update', context_system::instance()) || empty($CFG->block_course_list_tbird_hideallcourseslink)) {
                        $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">".get_string('fulllistofcourses').'</a> ...';
                    }
                } else {
                    //$this->content->icons[] = '';
                    $this->content->text = get_string('nocoursesyet');
                    if (has_capability('moodle/course:create', context_coursecat::instance($category->id))) {
                        $this->content->footer = '<a href="'.$CFG->wwwroot.'/course/edit.php?category='.$category->id.'">'.get_string("addnewcourse").'</a> ...';
                    }
                }
                $this->title = get_string('courses');
            }
        }

        return $this->content;
    }

    /**
     * Returns the role that best describes the course list block.
     *
     * @return string
     */
    public function get_aria_role() {
    	return 'navigation';
    }  
}



