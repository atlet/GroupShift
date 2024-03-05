<?php

/**
 * @package  local_groupshift
 * @copyright 2024, Andraž Prinčič <atletek@gmail.com>
 * @license MIT
 * @doc https://docs.moodle.org/dev/Plugin_files
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Hook function to add items to the administration block.
 *
 * @param settings_navigation $nav     Which menu.
 * @param context             $context Which context.
 */
function local_groupshift_extend_settings_navigation(settings_navigation $nav, context $context) {
    global $COURSE;

    if (isloggedin()) {
        $coursenode = $nav->get('courseadmin');
        if (has_any_capability(array(
            'local/groupshift:manage'
        ), $context)) {

            if ($coursenode) {
                $url = new moodle_url('/local/groupshift/index.php', array('id' => $COURSE->id));
                $coursenode->add(
                    get_string('menuentry', 'local_groupshift'),
                    $url,
                    navigation_node::TYPE_SETTING,
                );
            }
        }
    }
}