<?php

namespace local_groupshift\form;

use local_groupshift\utils\groupdata;

defined('MOODLE_INTERNAL') || die();

// moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class manage_form extends \moodleform {
    public function definition() {  
        
        $groupdata = new groupdata();

        $fromgroups = $groupdata->GetGroupsList($this->_customdata['courseid'], $this->_customdata['filtertype'], 'G', $this->_customdata['badge']);
        $togroups = $groupdata->allgroups($this->_customdata['courseid']);           

        // A reference to the form is stored in $this->form.
        // A common convention is to store it in a variable, such as `$mform`.
        $mform = $this->_form; // Don't forget the underscore!     

        $mform->addElement(
            'static',
            'description',
            '',
            get_string('managehelp', 'local_groupshift')
        );

        $mform->addElement('select', 'fromgroup', get_string('fromgroup', 'local_groupshift'), $fromgroups);
        $mform->addElement('select', 'togroup', get_string('togroup', 'local_groupshift'), $togroups);

        $this->add_action_buttons();
    }

    // Custom validation should be added here.
    function validation($data, $files) {
        return [];
    }
}
