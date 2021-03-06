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
 * The workshop module configuration variables
 *
 * The values defined here are often used as defaults for all module instances.
 *
 * @package    mod_udmworkshop
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/udmworkshop/locallib.php');

    $grades = udmworkshop::available_maxgrades_list();

    $settings->add(new admin_setting_configselect('udmworkshop/grade', get_string('submissiongrade', 'udmworkshop'),
                        get_string('configgrade', 'udmworkshop'), 80, $grades));

    $settings->add(new admin_setting_configselect('udmworkshop/gradinggrade', get_string('gradinggrade', 'udmworkshop'),
                        get_string('configgradinggrade', 'udmworkshop'), 20, $grades));

    $options = array();
    for ($i = 5; $i >= 0; $i--) {
        $options[$i] = $i;
    }
    $settings->add(new admin_setting_configselect('udmworkshop/gradedecimals', get_string('gradedecimals', 'udmworkshop'),
                        get_string('configgradedecimals', 'udmworkshop'), 0, $options));

    if (isset($CFG->maxbytes)) {
        $maxbytes = get_config('udmworkshop', 'maxbytes');
        $options = get_max_upload_sizes($CFG->maxbytes, 0, 0, $maxbytes);
        $settings->add(new admin_setting_configselect('udmworkshop/maxbytes', get_string('maxbytes', 'udmworkshop'),
                            get_string('configmaxbytes', 'udmworkshop'), 0, $options));
    }

    $settings->add(new admin_setting_configselect('udmworkshop/strategy', get_string('strategy', 'udmworkshop'),
                        get_string('configstrategy', 'udmworkshop'), 'accumulative', udmworkshop::available_strategies_list()));

    $options = udmworkshop::available_example_modes_list();
    $settings->add(new admin_setting_configselect('udmworkshop/examplesmode', get_string('examplesmode', 'udmworkshop'),
                        get_string('configexamplesmode', 'udmworkshop'), udmworkshop::EXAMPLES_VOLUNTARY, $options));

    // include the settings of allocation subplugins
    $allocators = core_component::get_plugin_list('workshopallocation');
    foreach ($allocators as $allocator => $path) {
        if (file_exists($settingsfile = $path . '/settings.php')) {
            $settings->add(new admin_setting_heading('workshopallocationsetting'.$allocator,
                    get_string('allocation', 'udmworkshop') . ' - ' . get_string('pluginname', 'workshopallocation_' . $allocator), ''));
            include($settingsfile);
        }
    }

    // include the settings of grading strategy subplugins
    $strategies = core_component::get_plugin_list('workshopform');
    foreach ($strategies as $strategy => $path) {
        if (file_exists($settingsfile = $path . '/settings.php')) {
            $settings->add(new admin_setting_heading('workshopformsetting'.$strategy,
                    get_string('strategy', 'udmworkshop') . ' - ' . get_string('pluginname', 'workshopform_' . $strategy), ''));
            include($settingsfile);
        }
    }

    // include the settings of grading evaluation subplugins
    $evaluations = core_component::get_plugin_list('workshopeval');
    foreach ($evaluations as $evaluation => $path) {
        if (file_exists($settingsfile = $path . '/settings.php')) {
            $settings->add(new admin_setting_heading('workshopevalsetting'.$evaluation,
                    get_string('evaluation', 'udmworkshop') . ' - ' . get_string('pluginname', 'workshopeval_' . $evaluation), ''));
            include($settingsfile);
        }
    }

}
