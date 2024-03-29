<?php

/**
 * @package  local_groupshift
 * @copyright 2024, Andraž Prinčič <atletek@gmail.com>
 * @license MIT
 * @doc https://docs.moodle.org/dev/Plugin_files
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

$id       = required_param('id', PARAM_INT);

$urlparams = array('id' => $id);

$course = $DB->get_record('course', array('id' => $id));

require_login($course);

$hdr = get_string('menuentry', 'local_groupshift');

if (!has_any_capability([
    'local/groupshift:manage',
], $PAGE->context)) {
    redirect($CFG->wwwroot);
}

$url = new moodle_url($CFG->wwwroot . '/local/groupshift/index.php', $urlparams);

$coursecontext = context_course::instance($course->id);
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('course');
$PAGE->set_url($url);
$PAGE->set_heading(format_string($course->fullname, true, array('context' => $coursecontext)) . ': ' . $hdr);
navigation_node::override_active_url(
    new moodle_url('/local/groupshift/index.php', array('id' => $course->id))
);

// Instantiate the myform form from within the plugin.
$mform = new \local_groupshift\form\index_form($url->__toString(), ['courseid' => $id]);

// Form processing and displaying is done here.
if ($mform->is_cancelled()) {
    // If there is a cancel element on the form, and it was pressed,
    // then the `is_cancelled()` function will return true.
    // You can handle the cancel operation here.
} else if ($fromform = $mform->get_data()) {
    $redirectparams = [
        'id' => $id,
        'filtertype' => $fromform->filtertype
    ];

    if(isset($fromform->badges)) {
        $redirectparams['badge'] = $fromform->badges;
    }

    $redirecturl = new moodle_url($CFG->wwwroot . '/local/groupshift/manage.php', $redirectparams);
    redirect($redirecturl);
    // When the form is submitted, and the data is successfully validated,
    // the `get_data()` function will return the data posted in the form.
} else {
    // This branch is executed if the form is submitted but the data doesn't
    // validate and the form should be redisplayed or on the first display of the form.

    // Set anydefault data (if any).   
}

$PAGE->set_title($hdr);

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();