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

require_login();

$course = $DB->get_record('course', array('id' => $id));

$hdr = get_string('menuentry', 'local_groupshift');
$returnurl = new moodle_url('/local/groupshift/index.php', $urlparams);
$PAGE->set_url($returnurl);

if (!has_any_capability(array(
    'local/groupshift:manage',
), $PAGE->context)) {
    redirect($CFG->wwwroot);
}

$PAGE->set_title($hdr);

echo $OUTPUT->header();