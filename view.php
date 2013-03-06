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
 * View all courses and programmes.
 *
 * @package    block
 * @subpackage course_level
 * @copyright  2012 University of London Computer Centre
 * @author     Ian Wild {@link http://moodle.org/user/view.php?id=81450}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//  Lists all the courses and programmes available on the site.

require_once('../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/filelib.php');

define('COURSE_SMALL_CLASS', 20);   // Below this is considered small.
define('COURSE_LARGE_CLASS', 200);  // Above this is considered large.
define('DEFAULT_PAGE_SIZE', 20);
define('SHOW_ALL_PAGE_SIZE', 5000);

define('COURSES_VIEW', 0);
define('PROGRAMMES_VIEW', 1);

$page         = optional_param('page', 0, PARAM_INT);                       // Which page to show.
$perpage      = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);    // How many per page.
// Make sure it is processed with p() or s() when sending to output!..
$search       = optional_param('search', '', PARAM_RAW);
$contextid    = optional_param('contextid', 0, PARAM_INT);                  // One of this or...
$courseid     = optional_param('id', 1, PARAM_INT);                         // Assume we're on the front page
$tab          = optional_param('tab', COURSES_VIEW, PARAM_INT);             // Browsing either courses or programmes.

$PAGE->set_url('/blocks/course_level/view.php', array(
    'page' => $page,
    'perpage' => $perpage,
    'search' => $search,
    'contextid' => $contextid,
    'id' => $courseid,
    'tab' => $tab));

// Make sure the context is right so 1) the user knows where they are, 2) the theme renders correctly.
if ($contextid) {
    $context = get_context_instance_by_id($contextid, MUST_EXIST);
    if ($context->contextlevel != CONTEXT_COURSE) {
        print_error('invalidcontext');
    }
    $course = $DB->get_record('course', array('id'=>$context->instanceid), '*', MUST_EXIST);
} else {
    $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
    $context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
}
// Not needed anymore...
unset($contextid);

require_login($course);

$systemcontext = get_context_instance(CONTEXT_SYSTEM);

// Maybe it's accessed from the front page???
$isfrontpage = ($course->id == SITEID);
$frontpagectx = get_context_instance(CONTEXT_COURSE, SITEID);

if ($isfrontpage) {
    $PAGE->set_pagelayout('admin');
    // TODO capability check?
} else {
    $PAGE->set_pagelayout('incourse');
    // TODO capability check?
}

$PAGE->set_title("$course->shortname: ".get_string('display_all', 'block_course_level'));
$PAGE->set_heading(get_string('display_all', 'block_course_level'));
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->add_body_class('path-block-course-level-display-all'); // So we can style it independently.
$PAGE->navbar->add(get_string('display_all', 'block_course_level'));

echo $OUTPUT->header();

require("tabs.php");

// Should use this variable so that we don't break stuff every time a variable is added or changed.
$baseurl = new moodle_url('/blocks/course_level/view.php', array(
    'contextid' => s($context->id),
    'id' => s($course->id),
    'perpage' => s($perpage),
    'tab' => s($tab),
    'search' => s($search)));

if ($tab == PROGRAMMES_VIEW) {
    // Viewing all programmes...
    echo '<div class="programmelist">';

    // Search...
    echo '<div class="filter"><form action="view.php" class="searchform"><div><input type="hidden" name="id" value="'.s($course->id)
            .'" /><input type="hidden" name="tab" value="'.s($tab).'" />';
    echo '<label for="search">' . get_string('programmesearch', 'block_course_level') . ' </label>';
    echo '<input type="text" id="search" name="search" value="'.s($search).'" />&nbsp;<input type="submit" value="'
            .get_string('search').'" /></div></form></div>'."\n";

    // Define a table showing a list of all courses.
    // Note: 'fullname' is treated as special in a flexible_table. Call the column 'course_fullname' instead.
    $tablecolumns = array('shortname', 'course_fullname', 'courses');
    $tableheaders = array(get_string('shortname', 'block_course_level'), get_string('fullname', 'block_course_level'),
        get_string('courses', 'block_course_level'));

    $table = new flexible_table('block-course-level-display-all-'.$course->id);
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($baseurl->out());

    $table->sortable(true, 'shortname', SORT_ASC);
    $table->sortable(true, 'course_fullname', SORT_ASC);
    // Set 'no_sorting' options if necessary... e.g.
    $table->no_sorting('courses');

    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'display_all');
    $table->set_attribute('class', 'generaltable generalbox');

    $table->set_control_variables(array(
        TABLE_VAR_SORT    => 'ssort',
        TABLE_VAR_HIDE    => 'shide',
        TABLE_VAR_SHOW    => 'sshow',
        TABLE_VAR_IFIRST  => 'sifirst',
        TABLE_VAR_ILAST   => 'silast',
        TABLE_VAR_PAGE    => 'spage'
    ));
    $table->setup();

    $table->initialbars(true);

    $ual_mis = new ual_mis;

    // List of programmes at the current visible page - paging makes it relatively short...

    // Display ordering...
    $sort = $table->get_sql_sort();
    if (!empty($sort)) {
        $sort = preg_replace('/course_fullname/', 'fullname', $sort);
        $sort = ' ORDER BY '.$sort;
    } else {
        $sort = '';
    }

    // Filtering programmes...
    $filter = '';
    if (!empty($search)) {
        // Note: am assuming the course must be visible - however,
        // this will depend on the user's capability in the current context.

        // I'm sure the following is not going to make for a very efficient query...
        $filter = ' AND visible=\'1\' AND (fullname LIKE \'%'.$search.'%\' OR shortname LIKE \'%'.$search.'%\')';
    }

    $programmelist = array();

    $totalcount = $ual_mis->get_programme_count($filter);

    $table->pagesize($perpage, $totalcount);

    $programmelist = $ual_mis->get_programme_range($table->get_page_start(), $table->get_page_size(), $filter, $sort);

    if ($totalcount < 1) {
        echo $OUTPUT->heading(get_string('nothingtodisplay'));
    } else {

        $programmesprinted = array();

        $dlgids = array();

        foreach ($programmelist as $course) {
            if (in_array($course->id, $programmesprinted)) {
                continue;
            }
            $data = array();

            $data[] = html_writer::link(new moodle_url('/course/view.php?id='.$course->id),
                $course->shortname);

            $data[] = html_writer::link(new moodle_url('/course/view.php?id='.$course->id),
                $course->fullname);


            // Output the links to this programme's courses...
            $progcourses = $ual_mis->get_programme_courses($course->idnumber, '', ' ORDER BY idnumber ASC');

            if (!empty($progcourses)) {
                $dlgid = $course->id;
                $dlgtitle = html_writer::tag('div', get_string('programme_courses', 'block_course_level'),
                                             array('class' => 'dlgTitle', 'id' => 'course-'.$dlgid));

                $cellcontents = '';

                $contentbox = '';

                $links = array(); // Start with an empty list of links.

                foreach($progcourses as $progcourse) {
                    $links[] = html_writer::link(new moodle_url('/course/view.php?id='.$progcourse->get_moodle_course_id()),
                                                 $progcourse->get_shortname().' - '.$progcourse->get_fullname(),
                                                 array('id' => 'course_link'));
                }

                // Implode the list of links and separate with a <br/>...
                $contentbox .= implode('<br/>', $links);

                $contentbox = html_writer::tag('div', $contentbox, array('class' => 'contentBox'));

                $cellcontents = html_writer::tag('div',
                                                 $dlgtitle.$contentbox, array('class' => 'yui3-overlay-loading',
                                                                              'id' => 'courseoverlay-' . $dlgid));

                $data[] = $cellcontents;

                // Remember what all the course dialog ids are. There will be one per course...
                $dlgids[] = $dlgid;
            } else {
                $data[] = get_string('nothingtodisplay');
            }

            $table->add_data($data, 'row-course-'.$course->id);
        }

        // Load up relevant Javascript, passing the course id's of all of the course units displayed on the page...
        $PAGE->requires->yui_module('moodle-block_course_level-courses',
            'M.blocks_course_level.init_courses',
            array(array('courseids' => $dlgids)));

        $table->print_html();
    }

    $perpageurl = clone($baseurl);
    $perpageurl->remove_params('perpage');
    if ($perpage == SHOW_ALL_PAGE_SIZE) {
        $perpageurl->param('perpage', DEFAULT_PAGE_SIZE);
        echo $OUTPUT->container(html_writer::link($perpageurl,
                                                  get_string('showperpage', '', DEFAULT_PAGE_SIZE)),
                                                  array(),
                                                  'showall');

    } else if ($totalcount > 0 && $perpage < $totalcount) {
        $perpageurl->param('perpage', SHOW_ALL_PAGE_SIZE);
        echo $OUTPUT->container(html_writer::link($perpageurl,
                                                  get_string('showall', '', $totalcount)),
                                                  array(),
                                                  'showall');
    }

    echo '</div>';  // ... programmelist ...

} else {
    // Viewing all courses...
    echo '<div class="courselist">';

    // Search...
    echo '<div class="filter"><form action="view.php" class="searchform"><div><input type="hidden" name="id" value="'
          .$course->id.'" /><input type="hidden" name="tab" value="'.$tab.'" />';
    echo '<label for="search">' . get_string('coursesearch', 'block_course_level') . ' </label>';
    echo '<input type="text" id="search" name="search" value="'.s($search).'" />&nbsp;<input type="submit" value="'
          .get_string('search').'" /></div></form></div>'."\n";

    $controlstable = new html_table();

    echo html_writer::table($controlstable);

    // Define a table showing a list of all courses
    // Note: 'fullname' is treated as special in a flexible_table. Call the column 'course_fullname' instead.
    $tablecolumns = array('shortname', 'course_fullname', 'units');
    $tableheaders = array(get_string('shortname', 'block_course_level'), get_string('fullname', 'block_course_level'),
       get_string('units', 'block_course_level'));

    $table = new flexible_table('block-course-level-display-all-'.$course->id);
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($baseurl->out());

    $table->sortable(true, 'shortname', SORT_ASC);
    $table->sortable(true, 'course_fullname', SORT_ASC);
    // Set 'no_sorting' options if necessary... e.g.

    $table->no_sorting('units');

    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'display_all');
    $table->set_attribute('class', 'generaltable generalbox');

    $table->set_control_variables(array(
        TABLE_VAR_SORT    => 'ssort',
        TABLE_VAR_HIDE    => 'shide',
        TABLE_VAR_SHOW    => 'sshow',
        TABLE_VAR_IFIRST  => 'sifirst',
        TABLE_VAR_ILAST   => 'silast',
        TABLE_VAR_PAGE    => 'spage'
    ));
    $table->setup();

    $table->initialbars(true);

    // List of courses at the current visible page - paging makes it relatively short...
    $ual_mis = new ual_mis;

    // Display ordering...
    $sort = $table->get_sql_sort();
    if (!empty($sort)) {
        $sort = preg_replace('/course_fullname/', 'fullname', $sort);
        $sort = ' ORDER BY '.$sort;
    } else {
        $sort = '';
    }

    // Filtering courses...
    $filter = '';
    $count_filter = '';
    if (!empty($search)) {
        // Note: am assuming the course must be visible.
        // However, this will depend on the user's capability in the current context.
        // NOTE: The following is not going to make for a very efficient query...
        $filter = ' AND visible=\'1\' AND (fullname LIKE \'%'.$search.'%\' OR shortname LIKE \'%'.$search.'%\')';
    }

    $courselist = array();

    $totalcount = $ual_mis->get_course_count($filter);

    $table->pagesize($perpage, $totalcount);

    $courselist = $ual_mis->get_course_range($table->get_page_start(), $table->get_page_size(), $filter, $sort);

    if ($totalcount < 1) {
        echo $OUTPUT->heading(get_string('nothingtodisplay'));
    } else {

        $coursesprinted = array();

        $dlgids = array();

        foreach ($courselist as $course) {
            if (in_array($course->id, $coursesprinted)) {
                continue;
            }
            $data = array();


            $data[] = html_writer::link(new moodle_url('/course/view.php?id='.$course->id),
                $course->shortname);

            $data[] = html_writer::link(new moodle_url('/course/view.php?id='.$course->id),
                $course->fullname);


            // Output links to this course's units.
            $course_code_no_year = substr($course->idnumber, 0, 8).'?'.substr($course->idnumber,-6, 6);
            $courseyears = $ual_mis->get_course_years_units($course_code_no_year, '', ' ORDER BY idnumber ASC');

            if (!empty($courseyears)) {

                $dlgid = $course->id;
                $dlgtitle = html_writer::tag('div', get_string('years_and_units', 'block_course_level'),
                                                                array('class' => 'dlgTitle', 'id' => 'units-'.$dlgid));

                $cellcontents = '';

                // Write list of years and units
                $contentbox = '';
                foreach($courseyears as $courseyear=>$units) {
                    $year_homepage = $ual_mis->get_course_from_idnumber($courseyear);
                    if($year_homepage != null) {
                        $year = intval(substr($year_homepage->idnumber, -7, 1));
                        // The course fullname should contain the 'Course years' full title (which includes which year it is)
                        $homepagelink = html_writer::link(new moodle_url('/course/view.php?id='.$year_homepage->id), $year_homepage->fullname);
                        $contentbox .= html_writer::tag('h4', $homepagelink, array('id' => 'year_heading'));
                    } else {
                        $contentbox .= html_writer::tag('h4', get_string('year_unknown', 'block_course_level'), array('id' => 'year_heading'));
                    }

                    $links = array(); // Start with an empty list of links.
                    foreach ($units as $unit) {
                        $links[] = html_writer::link(new moodle_url('/course/view.php?id='.$unit->get_moodle_course_id()),
                                                                    $unit->get_shortname().' - '.$unit->get_fullname(),
                                                     array('id' => 'unit_link'));
                    }
                    // Implode the list of links and separate with a <br/>...
                    $contentbox .= implode('<br/>', $links);
                }

                $contentbox = html_writer::tag('div', $contentbox, array('class' => 'contentBox'));

                $cellcontents .= html_writer::tag('div',
                    $dlgtitle.$contentbox,
                    array('class' => 'yui3-overlay-loading',
                        'id' => 'unitoverlay-' . $dlgid));

                $data[] = $cellcontents;

                // Remember what all the course dialog ids are. There will be one per course...
                $dlgids[] = $dlgid;
            } else {
                $data[] = get_string('nothingtodisplay');
            }

            $table->add_data($data, 'row-course-'.$course->id);
        }

        // Load up relevant Javascript, passing the course id's of all of the course units displayed on the page...
        $PAGE->requires->yui_module('moodle-block_course_level-units',
            'M.blocks_course_level.init_units',
            array(array('unitids' => $dlgids)));

        $table->print_html();
    }

    $perpageurl = clone($baseurl);
    $perpageurl->remove_params('perpage');
    if ($perpage == SHOW_ALL_PAGE_SIZE) {
        $perpageurl->param('perpage', DEFAULT_PAGE_SIZE);
        echo $OUTPUT->container(html_writer::link($perpageurl,
                                                  get_string('showperpage', '', DEFAULT_PAGE_SIZE)),
                                                  array(),
                                                  'showall');

    } else if ($totalcount > 0 && $perpage < $totalcount) {
        $perpageurl->param('perpage', SHOW_ALL_PAGE_SIZE);
        echo $OUTPUT->container(html_writer::link($perpageurl,
                                                  get_string('showall', '', $totalcount)),
                                                  array(),
                                                  'showall');
    }

    echo '</div>';  // ... courselist ...
}

print_tabbed_table_end();

echo $OUTPUT->footer();

/**
 * Ensure HTML is correctly formed.
 */
function print_tabbed_table_end() {
    echo "</div></div>";
}
