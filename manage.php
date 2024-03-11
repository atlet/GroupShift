<?php

/**
 * @package  local_groupshift
 * @copyright 2024, Andraž Prinčič <atletek@gmail.com>
 * @license MIT
 * @doc https://docs.moodle.org/dev/Plugin_files
 */

 use local_groupshift\utils\groupdata;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

$id       = required_param('id', PARAM_INT);
$filtertype = required_param('filtertype', PARAM_TEXT);
$badge = optional_param('badge', -1, PARAM_INT);

$urlparams = array('id' => $id, 'filtertype' => $filtertype, 'badge' => $badge);

$course = $DB->get_record('course', array('id' => $id));

require_login($course);

$hdr = get_string('menuentry', 'local_groupshift');

if (!has_any_capability([
    'local/groupshift:manage',
], $PAGE->context)) {
    redirect($CFG->wwwroot);
}

$url = new moodle_url($CFG->wwwroot . '/local/groupshift/manage.php', $urlparams);

$coursecontext = context_course::instance($course->id);
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('course');
$PAGE->set_url($url);
$PAGE->set_heading(format_string($course->fullname, true, array('context' => $coursecontext)) . ': ' . $hdr);
navigation_node::override_active_url(
    $url
);

// Instantiate the myform form from within the plugin.
$mform = new \local_groupshift\form\manage_form($url->out(false), ['courseid' => $id, 'filtertype' => $filtertype, 'badge' => $badge]);
$groupdata = new groupdata();

// Form processing and displaying is done here.
if ($mform->is_cancelled()) {
    $cancelurl = new moodle_url($CFG->wwwroot . '/local/groupshift/index.php', ['id' => $id]);
    redirect($cancelurl);
    // If there is a cancel element on the form, and it was pressed,
    // then the `is_cancelled()` function will return true.
    // You can handle the cancel operation here.
} else if ($fromform = $mform->get_data()) {
    if ($fromform->fromgroup == $fromform->togroup) {
        \core\notification::error(get_string('selectdifferentgroups', 'local_groupshift'));    
    } else {        
        $groupdata->MoveUsers($id, $filtertype, $fromform->fromgroup, $fromform->togroup, $badge);
        \core\notification::success(get_string('sucesfullymoved', 'local_groupshift'));     
        redirect($url);    
    }    
    // When the form is submitted, and the data is successfully validated,
    // the `get_data()` function will return the data posted in the form.
} else {
    // This branch is executed if the form is submitted but the data doesn't
    // validate and the form should be redisplayed or on the first display of the form.

    // Set anydefault data (if any).
    $mform->set_data($toform);    
}

$PAGE->set_title($hdr);

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();