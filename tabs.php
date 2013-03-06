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
 * @package    block
 * @subpackage course_level
 * @copyright  2012 University of London Computer Centre
 * @author     Ian Wild {@link http://moodle.org/user/view.php?id=325899}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Tabs on 'All Courses & Programmes' page
 *
 * @copyright 2012 Ian Wild
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!isset($sortorder)) {
    $sortorder = '';
}
if (!isset($sortkey)) {
    $sortkey = '';
}

// Make sure variables are properly cleaned.
$sortkey   = clean_param($sortkey, PARAM_ALPHA);// Sorted view: CREATION | UPDATE | FIRSTNAME | LASTNAME...
$sortorder = clean_param($sortorder, PARAM_ALPHA);   // It defines the order of the sorting (ASC or DESC).

$toolsrow = array();
$browserow = array();
$inactive = array();
$activated = array();



$browserow[] = new tabobject(PROGRAMMES_VIEW,
                             $CFG->wwwroot.'/blocks/course_level/view.php?id='.$courseid.'&amp;tab='.PROGRAMMES_VIEW,
                             get_string('programmesview', 'block_course_level'));

$browserow[] = new tabobject(COURSES_VIEW,
    $CFG->wwwroot.'/blocks/course_level/view.php?id='.$courseid.'&amp;tab='.COURSES_VIEW,
    get_string('coursesview', 'block_course_level'));


if ($tab < COURSES_VIEW || $tab > PROGRAMMES_VIEW) {   // We are on second row.
    $inactive = array('edit');
    $activated = array('edit');

    $browserow[] = new tabobject('edit', '#', get_string('edit'));
}

// Put all this info together...

$tabrows = array();
$tabrows[] = $browserow;     // Always put these at the top.
if ($toolsrow) {
    $tabrows[] = $toolsrow;
}


?>
<div class="courseleveldisplay">


<?php print_tabs($tabrows, $tab, $inactive, $activated); ?>

<div class="entrybox">

