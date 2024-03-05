<?php

/**
 * @package  local_groupshift
 * @copyright 2024, Andraž Prinčič <atletek@gmail.com>
 * @license MIT
 * @doc https://docs.moodle.org/dev/Access_API
 */

// If you change this file, you must upgrade the plugin version inorder for your
// changes to take effect.

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/groupshift:manage' => [
        'riskbitmask' => RISK_DATALOSS, // https://docs.moodle.org/dev/Hardening_new_Roles_system
        'captype' => 'write', // read or write
        'contextlevel' => CONTEXT_COURSE, // https://docs.moodle.org/dev/Roles#Context
        'archetypes' => [ // https://docs.moodle.org/dev/Role_archetypes (What are archetypes)
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager'         => CAP_ALLOW,
        ],
    ]
];
