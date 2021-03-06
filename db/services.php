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
 * Workshop webservice functions.
 *
 *
 * @package    mod_udmworkshop
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2017 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(

    // Workshop related functions.
    'mod_udmworkshop_data_for_wizard_navigation_page' => array(
        'classname'    => 'mod_udmworkshop\external',
        'methodname'   => 'data_for_wizard_navigation_page',
        'classpath'    => '',
        'description'  => 'Loads the data required to render the wizard_navigation_page',
        'type'         => 'read',
        'capabilities' => 'moodle/course:manageactivities',
        'ajax'         => true,
    )
);

