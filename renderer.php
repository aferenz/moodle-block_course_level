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

    /**
     * Prints course level tree view
     * @return string
     */
    public function course_level_tree() {
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

        $module = array('name'=>'block_course_level', 'fullpath'=>'/blocks/course_level/module.js', 'requires'=>array('yui2-treeview'));

        if (empty($tree) ) {
            $html = $this->output->box(get_string('nocourses', 'block_course_level'));
        } else {

            $htmlid = 'course_level_tree_'.uniqid();
            $this->page->requires->js_init_call('M.block_course_level.init_tree', array(false, $htmlid));
            $html = '<div id="'.$htmlid.'">';
            $html .= $this->htmllize_tree($tree->courses);
            $html .= '</div>';
        }

        // Add 'View all courses' link to bottom of block...
        $html .= html_writer::empty_tag('hr');
        // TODO This needs to link somewhere!
        $viewcourses_lnk = '#';
        $attributes = array();
        $html .= html_writer::link($viewcourses_lnk, get_string('view_all_courses', 'block_course_level'), $attributes);

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

        foreach ($tree as $node) {

            $course_shortname = $node->get_shortname();
            $attributes = array('title'=>$course_shortname);
            $moodle_url = $CFG->wwwroot.'/course/view.php?id='.$node->get_id();
            $content = html_writer::link($moodle_url, $course_shortname, $attributes);
            $attributes = array('yuiConfig'=>json_encode($yuiconfig));

            $children = $node->get_children();
            $parentids = $node->get_parentids();

            if($children == null) {
                // if this course has parents and indent>0 then display it.
                if($indent>0) {
                    $result .= html_writer::tag('li', $content, $attributes);
                } elseif (!isset($parentids)) {
                    $result .= html_writer::tag('li', $content, $attributes);
                }

            } else {
                // if this has parents OR it doesn't have parents or children then we need to display it...???
                $result .= html_writer::tag('li', $content.$this->htmllize_tree($children, $indent+1), $attributes);
            }
        }
        $result .= '</ul>';

        return $result;
    }
}


