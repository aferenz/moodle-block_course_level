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
 * Print course level tree
 *
 * @package    block_course_level
 * @copyright  2012 University of London Computer Centre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/blocks/course_level/lib.php');

class block_course_level_renderer extends plugin_renderer_base {

    private $showcode = 0;
    private $trimmode = block_course_level::TRIM_RIGHT;
    private $trimlength = 50;
    private $courseid = 0;

    /**
     * Prints course level tree view
     * @return string
     */
    public function course_level_tree($showcode, $trimmode, $trimlength, $courseid) {
        $this->showcode = $showcode;
        $this->trimmode = $trimmode;
        $this->trimlength = $trimlength;
        $this->courseid = $courseid;

        return $this->render(new course_level_tree);
    }

    /**
     * provides the html contained in the course level block - including the tree itself and the links at the bottom
     * of the block to 'all courses' and 'all programmes'.
     *
     * @param render_course_level_tree $tree
     * @return string
     */
    public function render_course_level_tree(course_level_tree $tree) {
        global $CFG;

        $module = array('name'=>'block_course_level',
                        'fullpath'=>'/blocks/course_level/module.js',
                        'requires'=>array('yui2-treeview'));

        if (empty($tree) ) {
            $html = $this->output->box(get_string('nocourses', 'block_course_level'));
        } else {

            $htmlid = 'course_level_tree_'.uniqid();
            $this->page->requires->js_init_call('M.block_course_level.init_tree', array(false, $htmlid));
            $html = '<div id="'.$htmlid.'">';
            $html .= $this->htmllize_tree($tree->courses);
            $html .= '</div>';
        }

        if(!empty($tree->orphaned_courses)) {
            // Display orphaned courses at bottom of block...

            $html .= html_writer::empty_tag('hr');

            $orphaned_courses_list = html_writer::tag('h4', get_string('orphaned_courses', 'block_course_level'));
            $attributes = array('id' => 'orphaned-courses-heading');
            $orphaned_courses_list = html_writer::tag('div', $orphaned_courses_list, $attributes);

            $orphaned_courses_list .= html_writer::start_tag('ul');

            foreach($tree->orphaned_courses as $orphaned_course) {
                $name = $this->trim($orphaned_course->get_fullname());
                if($this->showcode == 1) {
                    $name .= ' ('.$orphaned_course->get_idnumber().')';
                }
                // Fix to bug UALMOODLE-58: look for ampersand in fullname and replace it with entity
                $name = preg_replace('/&(?![#]?[a-z0-9]+;)/i', "&amp;$1", $name);

                $moodle_url = $CFG->wwwroot.'/course/view.php?id='.$orphaned_course->get_moodle_course_id();
                $content = html_writer::link($moodle_url, $name, $attributes);
                $orphaned_courses_list .= html_writer::tag('li', $content, $attributes);

            }
            $orphaned_courses_list .= html_writer::end_tag('ul');

            $attributes = array('class' => 'orphaned-courses');
            $orphaned_courses_list = html_writer::tag('div', $orphaned_courses_list, $attributes);

            $html .= $orphaned_courses_list;
        }

        if(!empty($tree->orphaned_units)) {
            // Display orphaned units at bottom of block...

            $html .= html_writer::empty_tag('hr');

            $orphaned_units_list = html_writer::tag('h4', get_string('orphaned_units', 'block_course_level'));
            $attributes = array('id' => 'orphaned-units-heading');
            $orphaned_units_list = html_writer::tag('div', $orphaned_units_list, $attributes);

            $orphaned_units_list .= html_writer::start_tag('ul');

            foreach($tree->orphaned_units as $orphaned_unit) {
                $name = $this->trim($orphaned_unit->get_fullname());
                if($this->showcode == 1) {
                    $name .= ' ('.$orphaned_unit->get_idnumber().')';
                }
                // Fix to bug UALMOODLE-58: look for ampersand in fullname and replace it with entity
                $name = preg_replace('/&(?![#]?[a-z0-9]+;)/i', "&amp;$1", $name);

                $moodle_url = $CFG->wwwroot.'/course/view.php?id='.$orphaned_unit->get_moodle_course_id();
                $content = html_writer::link($moodle_url, $name, $attributes);
                $orphaned_units_list .= html_writer::tag('li', $content, $attributes);

            }
            $orphaned_units_list .= html_writer::end_tag('ul');

            $attributes = array('class' => 'orphaned-courses');
            $orphaned_units_list = html_writer::tag('div', $orphaned_units_list, $attributes);

            $html .= $orphaned_units_list;
        }


        // Add 'View all courses' link to bottom of block...
        $html .= html_writer::empty_tag('hr');
        $viewcourses_lnk = $CFG->wwwroot.'/blocks/course_level/view.php?id='.$this->courseid;
        $attributes = array('class' => 'view-all');
        $span = html_writer::tag('span', '');
        $html .= html_writer::link($viewcourses_lnk, get_string('view_all_courses', 'block_course_level').$span, $attributes);

        return $html;
    }

    /**
     * Converts the course tree into something more meaningful.
     *
     * @param $tree
     * @param int $indent
     * @return string
     */
    protected function htmllize_tree($tree, $indent=0) {
        global $CFG;

        $yuiconfig = array();
        $yuiconfig['type'] = 'html';

        $result = '<ul>';

        if (!empty($tree)) {
            foreach ($tree as $node) {

                $name = $this->trim($node->get_fullname());
                if($this->showcode == 1) {
                    $name .= ' ('.$node->get_idnumber().')';
                }
                // Fix to bug UALMOODLE-58: look for ampersand in fullname and replace it with entity
                $name = preg_replace('/&(?![#]?[a-z0-9]+;)/i', "&amp;$1", $name);

                $attributes = array('id' => $indent);

                $node_id = $node->get_moodle_course_id();

                if($node_id == 0) {
                    $span = html_writer::tag('span', '');
                    $content = html_writer::tag('strong', $name.$span, $attributes);
                } else {
                    // Create a link
                    $attributes['title'] = $name;
                    // If the user is enrolled on this course then show a link...
                    if($node->get_user_enrolled() == true) {
                        $moodle_url = $CFG->wwwroot.'/course/view.php?id='.$node->get_moodle_course_id();
                        $content = html_writer::link($moodle_url, $name, $attributes);
                    } else {
                        // Display the name but it's not clickable...
                        // TODO make this a configuration option...
                        $content = html_writer::tag('i', $name);
                    }
                }

                $attributes = array('yuiConfig'=>json_encode($yuiconfig));

                $children = $node->get_children();
                $parents = $node->get_parents();

                if ($children == null) {
                    // If this course has parents and indent>0 then display it.
                    if ($indent>0) {
                        $result .= html_writer::tag('li', $content, $attributes);
                    } else if (!empty($parents)) {
                        $result .= html_writer::tag('li', $content, $attributes);
                    }

                } else {
                    // If this has parents OR it doesn't have parents or children then we need to display it...???
                    if($indent != 0) {
                        $attributes['class'] = 'expanded';
                    }
                    $result .= html_writer::tag('li', $content.$this->htmllize_tree($children, $indent+1), $attributes);
                }
            }
        }
        $result .= '</ul>';

        return $result;
    }

    /**
     * Trims the text and shorttext properties of this node and optionally
     * all of its children.
     *
     * @param string $text The text to truncate
     * @return string
     */
    private function trim($text) {
        $result = $text;

        switch ($this->trimmode) {
            case block_course_level::TRIM_RIGHT :
                if (textlib::strlen($text)>($this->trimlength+3)) {
                    // Truncate the text to $long characters.
                    $result = textlib::substr($text, 0, $this->trimlength).'...';
                }
                break;
            case block_course_level::TRIM_LEFT :
                if (textlib::strlen($text)>($this->trimlength+3)) {
                    // Truncate the text to $long characters.
                    $result = '...'.textlib::substr($text, textlib::strlen($text)-$this->trimlength, $this->trimlength);
                }
                break;
            case block_course_level::TRIM_CENTER :
                if (textlib::strlen($text)>($this->trimlength+3)) {
                    // Truncate the text to $long characters.
                    $length = ceil($this->trimlength/2);
                    $start = textlib::substr($text, 0, $length);
                    $end = textlib::substr($text, textlib::strlen($text)-$this->trimlength);
                    $result = $start.'...'.$end;
                }
                break;
        }
        return $result;
    }
}


