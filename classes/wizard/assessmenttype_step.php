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
 * This file defines the wizard step class for the assessment types.
 *
 * @package    mod_udmworkshop
 * @author     Gilles-Philippe Leblanc <gilles-philippe.leblanc@umontreal.ca>
 * @copyright  2017 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_udmworkshop\wizard;

defined('MOODLE_INTERNAL') || die();

/**
 * The wizard step class for the assessment type.
 *
 * @author     Gilles-Philippe Leblanc <gilles-philippe.leblanc@umontreal.ca>
 * @copyright  2017 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assessmenttype_step extends step {

    /** @var string NAME The name of the step */
    const NAME = 'assessmenttype';

    /**
     * Saves the assessment type form elements.
     *
     * @param \stdclass $data Raw data as returned by the form editor
     */
    public function save_form(\stdclass $data) {
        global $DB;

        $this->workshop->assessmenttype = $data->assessmenttype;

        if ($data->assessmenttype == \workshop::PEER_ASSESSMENT) {
            $this->workshop->useselfassessment = false;
        } else {
            $this->workshop->useselfassessment = true;
        }
        if ($data->assessmenttype == \workshop::SELF_ASSESSMENT) {
            $this->workshop->assesswithoutsubmission = 0;
        }
        $this->workshop->timemodified = time();
        $DB->update_record('udmworkshop', $this->workshop->get_record());

    }

    /**
     * Get the previous step of this step.
     *
     * @return string The previous step of this step
     */
    public function get_previous() {
        return null;
    }

    /**
     * Get the next step of this step.
     *
     * @return string The next step of this step
     */
    public function get_next() {
        return gradingmethod_step::NAME;
    }

}
