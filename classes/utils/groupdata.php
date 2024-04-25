<?php

namespace local_groupshift\utils;

use stdClass;

defined('MOODLE_INTERNAL') || die();

class groupdata {

    public $TYPE_GROUP = 'G';
    public $TYPE_USER = 'U';

    public $FILTER_BA = 'BA';
    public $FILTER_BNA = 'BNA';
    public $FILTER_SA = 'SA';
    public $FILTER_SNA = 'SNA';

    public function allgroups($courseid) {
        global $DB;

        $allGroups = $DB->get_records_sql("
        SELECT g.id, g.name AS Groupname, (SELECT COUNT(*) FROM {groups_members} gm WHERE gm.groupid = g.id) nofu
            FROM {course}         AS c
            JOIN {groups}         AS g ON g.courseid = c.id
            WHERE c.id = {$courseid}
        ");

        $notInGroup = $DB->get_record_sql("
        SELECT count(distinct u.id) nofu
            FROM {user} AS u
            left JOIN {user_enrolments} AS ue ON ue.userid = u.id        
            left JOIN {enrol} AS en ON en.id= ue.enrolid 
            LEFT JOIN {groups_members} gm ON u.id = gm.userid and gm.groupid in (SELECT g.id FROM mdl_groups as g where g.courseid = {$courseid})
            WHERE en.courseid = {$courseid}
            AND gm.id is null;
        ");

        $togroups = [];

        $togroups[-1] = get_string('notingroup', 'local_groupshift') . " ({$notInGroup->nofu})";

        foreach ($allGroups as $key => $value) {
            $togroups[$value->id] = "{$value->groupname} ({$value->nofu})";
        }

        return $togroups;
    }

    public function SelectedFilterDescription($filtertype, $badge) {
        global $DB;
        $r = '';

        switch ($filtertype) {
            case $this->FILTER_BA:
                $r = get_string('badgeachieved', 'local_groupshift');
                break;
            case $this->FILTER_BNA:
                $r = get_string('badgenotachieved', 'local_groupshift');
                break;
            case $this->FILTER_SA:
                $r = get_string('subjectcompleted', 'local_groupshift');
                break;
            case $this->FILTER_SNA:
                $r = get_string('subjectnocompleted', 'local_groupshift');
                break;
        }

        if ($badge > -1) {
            $badge = $DB->get_record('badge', ['id' => $badge]);

            $r = "{$r}: {$badge->name}";
        }

        return $r;
    }

    public function GetGroupsList($courseid, $filtertype, $type = 'G', $badge = -1) {
        global $DB;

        $fromgroups = [];

        list($sqlgroup, $sqlnogroup) = $this->getSQL($courseid, $filtertype, $type, $badge);

        $fromallGroups = $DB->get_records_sql($sqlgroup);
        $fromnotInGroup = $DB->get_record_sql($sqlnogroup);

        if ($fromnotInGroup->nofu > 0) {
            $fromgroups[-1] = get_string('notingroup', 'local_groupshift') . " ({$fromnotInGroup->nofu})";
        }

        foreach ($fromallGroups as $key => $value) {
            $fromgroups[$value->id] = "{$value->groupname} ({$value->nofu})";
        }

        return $fromgroups;
    }

    private function GetUsers($courseid, $filtertype, $fromgroup, $badge = -1) {
        global $DB;
        $allusers = [];

        list($sqlgroup, $sqlnogroup) = $this->getSQL($courseid, $filtertype, $this->TYPE_USER, $badge, $fromgroup);

        if ($fromgroup > 0) {
            $allusers = $DB->get_records_sql($sqlgroup);
        } else {
            $allusers = $DB->get_records_sql($sqlnogroup);
        }

        return $allusers;
    }

    public function MoveUsers($courseid, $filtertype, $fromgroup, $togroup, $badge = -1) {
        global $DB;

        $allusers = $this->GetUsers($courseid, $filtertype, $fromgroup, $badge);

        foreach ($allusers as $key => $value) {
            if ($fromgroup == -1) {
                $user = new stdClass();
                $user->groupid = $togroup;
                $user->userid = $value->id;

                $DB->insert_record('groups_members', $user);
            } else if ($togroup == -1) {
                $DB->delete_records('groups_members', ['groupid' => $fromgroup, 'userid' => $value->id]);
            } else {
                $user = $DB->get_record('groups_members', ['groupid' => $fromgroup, 'userid' => $value->id]);
                $user->groupid = $togroup;
                $DB->update_record('groups_members', $user);
            }
        }
    }

    private function getSQL($courseid, $filtertype, $type = 'G', $badge = -1, $fromgroup = null) {
        $sgroup = '';
        $snogroup = '';
        $groupby = '';
        $onlygroup = '';

        $sqlgroup = '';
        $sqlnogroup = '';

        switch ($type) {
            case $this->TYPE_GROUP:
                $sgroup = 'g.id, g.name AS Groupname, count(u.id) nofu';
                $snogroup = 'COUNT(DISTINCT u.id) nofu';
                $groupby = 'GROUP BY g.id';
                break;

            case $this->TYPE_USER:
                $sgroup = 'DISTINCT u.id';
                $snogroup = 'DISTINCT u.id';

                if (!is_null($fromgroup) && $fromgroup > 0) {
                    $onlygroup = "AND m.groupid = {$fromgroup}";
                }
                break;
        }

        switch ($filtertype) {
            case $this->FILTER_BA:
                $sqlgroup = "
                    SELECT {$sgroup}
                        FROM {course}         AS c
                        JOIN {groups}         AS g ON g.courseid = c.id
                        JOIN {groups_members} AS m ON g.id       = m.groupid
                        JOIN {user}           AS u ON m.userid   = u.id
                        LEFT JOIN {badge_issued} AS bi ON bi.userid = u.id AND bi.badgeid = {$badge}
                        WHERE c.id = {$courseid}
                        AND bi.userid IS NOT NULL
                        {$onlygroup} {$groupby}
                    ";

                $sqlnogroup = "
                    SELECT {$snogroup}
                    FROM {user} AS u
                    left JOIN {user_enrolments} AS ue ON ue.userid = u.id        
                    left JOIN {enrol} AS en ON en.id= ue.enrolid 
                    LEFT JOIN {groups_members} gm ON u.id = gm.userid and gm.groupid in (SELECT g.id FROM mdl_groups as g where g.courseid = {$courseid})
                    LEFT JOIN {badge_issued} AS bi ON bi.userid = u.id AND bi.badgeid = {$badge}
                    WHERE en.courseid = {$courseid}
                    AND bi.userid IS NOT NULL
                    AND gm.id is null;
                    ";
                break;

            case $this->FILTER_BNA:
                $sqlgroup = "
                    SELECT {$sgroup}
                        FROM {course}         AS c
                        JOIN {groups}         AS g ON g.courseid = c.id
                        JOIN {groups_members} AS m ON g.id       = m.groupid
                        JOIN {user}           AS u ON m.userid   = u.id
                        LEFT JOIN {badge_issued} AS bi ON bi.userid = u.id AND bi.badgeid = {$badge}
                        WHERE c.id = {$courseid}
                        AND bi.userid IS NULL
                        {$onlygroup} {$groupby}
                    ";

                $sqlnogroup = "
                    SELECT {$snogroup}
                        FROM {user} AS u
                        left JOIN {user_enrolments} AS ue ON ue.userid = u.id        
                        left JOIN {enrol} AS en ON en.id= ue.enrolid 
                        LEFT JOIN {groups_members} gm ON u.id = gm.userid and gm.groupid in (SELECT g.id FROM mdl_groups as g where g.courseid = {$courseid})
                        LEFT JOIN {badge_issued} AS bi ON bi.userid = u.id AND bi.badgeid = {$badge}
                        WHERE en.courseid = {$courseid}
                        AND bi.userid IS NULL
                        AND gm.id is null;
                    ";
                break;

            case $this->FILTER_SA:
                $sqlgroup = "
                    SELECT {$sgroup}
                        FROM {course}         AS c
                        JOIN {groups}         AS g ON g.courseid = c.id
                        JOIN {groups_members} AS m ON g.id       = m.groupid
                        JOIN {user}           AS u ON m.userid   = u.id
                        LEFT JOIN {course_completions} AS cc ON cc.userid = u.id AND cc.course = {$courseid}
                        WHERE c.id = {$courseid}
                        AND cc.userid IS NOT NULL
                        AND cc.timecompleted IS NOT NULL
                        {$onlygroup} {$groupby}
                    ";

                $sqlnogroup = "
                    SELECT {$snogroup}
                        FROM {user} AS u
                        left JOIN {user_enrolments} AS ue ON ue.userid = u.id        
                        left JOIN {enrol} AS en ON en.id= ue.enrolid 
                        LEFT JOIN {groups_members} gm ON u.id = gm.userid and gm.groupid in (SELECT g.id FROM mdl_groups as g where g.courseid = {$courseid})
                        LEFT JOIN {course_completions} AS cc ON cc.userid = u.id AND cc.course = {$courseid}
                        WHERE en.courseid = {$courseid}
                        AND cc.userid IS NOT NULL
                        AND cc.timecompleted IS NOT NULL
                        AND gm.id is null;
                    ";
                break;

            case $this->FILTER_SNA:
                $sqlgroup = "
                    SELECT {$sgroup}
                        FROM {course}         AS c
                        JOIN {groups}         AS g ON g.courseid = c.id
                        JOIN {groups_members} AS m ON g.id       = m.groupid
                        JOIN {user}           AS u ON m.userid   = u.id
                        LEFT JOIN {course_completions} AS cc ON cc.userid = u.id AND cc.course = {$courseid} AND cc.timecompleted IS NOT NULL
                        WHERE c.id = {$courseid}
                        AND cc.userid IS NULL
                        {$onlygroup} {$groupby}
                    ";

                $sqlnogroup = "
                    SELECT {$snogroup}
                        FROM {user} AS u
                        left JOIN {user_enrolments} AS ue ON ue.userid = u.id        
                        left JOIN {enrol} AS en ON en.id= ue.enrolid 
                        LEFT JOIN {groups_members} gm ON u.id = gm.userid and gm.groupid in (SELECT g.id FROM mdl_groups as g where g.courseid = {$courseid})
                        LEFT JOIN {course_completions} AS cc ON cc.userid = u.id AND cc.course = {$courseid} AND cc.timecompleted IS NOT NULL
                        WHERE en.courseid = {$courseid}
                        AND cc.userid IS NULL                        
                        AND gm.id is null;
                    ";
                break;
        }

        return [$sqlgroup, $sqlnogroup];
    }
}
