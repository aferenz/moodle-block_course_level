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
 * Global settings page.
 *
 * @package    block
 * @subpackage course_level
 * @copyright  2012-13 University of London Computer Centre
 * @author     Ian Wild {@link http://moodle.org/user/view.php?id=325899}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext('block_course_level/admin_tool_url', get_string('admin_tool_url', 'block_course_level'), '',
        get_string('default_admin_tool_url', 'block_course_level'), PARAM_URL));

    $settings->add(new admin_setting_configtext('block_course_level/admin_tool_magic_text', get_string('admin_tool_magic', 'block_course_level'), '',
        get_string('default_admin_tool_magic', 'block_course_level'), PARAM_TEXT));

}
