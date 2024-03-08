<?php

namespace local_groupshift\form;

defined('MOODLE_INTERNAL') || die();

// moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class index_form extends \moodleform {
    // Add elements to form.
    public function definition() {
        global $DB;
        // A reference to the form is stored in $this->form.
        // A common convention is to store it in a variable, such as `$mform`.
        $mform = $this->_form; // Don't forget the underscore!

        $options = [
            'BA' => get_string('badgeachieved', 'local_groupshift'),
            'BNA' => get_string('badgenotachieved', 'local_groupshift'),
            'SA' => get_string('subjectcompleted', 'local_groupshift'),
            'SNA' => get_string('subjectnocompleted', 'local_groupshift')
        ];

        $records = $DB->get_records_sql("SELECT b.id, b.name, b.description
        ,CASE
          WHEN b.type = 1 THEN 'System'
          WHEN b.type = 2 THEN 'Course'
        END AS Level
        FROM {badge} AS b
        JOIN {course} AS c ON c.id = {$this->_customdata['courseid']}");

        $badges = [];

        foreach ($records as $key => $value) {
            $badges[$value->id] = "{$value->name} - {$value->description}";
        }

        $mform->addElement('static', 'description', '',
        get_string('help', 'local_groupshift'));

        $mform->addElement('select', 'filtertype', get_string('filtertype', 'local_groupshift'), $options);
        $mform->addElement('select', 'badges', get_string('badges', 'local_groupshift'), $badges);

        $mform->disabledIf('badges', 'filtertype', 'in', ['SA', 'SNA']);

        $this->add_action_buttons();
    }

    // Custom validation should be added here.
    function validation($data, $files) {
        return [];
    }
}
