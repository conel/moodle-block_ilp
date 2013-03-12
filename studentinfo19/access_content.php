<?php
/**
 * Performs the permissions checks on the current user
 *
 * @copyright &copy; 2009 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lpr
 * @version 1.0
 */

// get the site context
$sitecontext = get_context_instance(CONTEXT_SYSTEM);

// we need to check the capabilities against all courses
if(isset($USER->access)) {
    $accessinfo = $USER->access;
} else {
    $accessinfo = $USER->access = get_user_access_sitewide($USER->id);
}

/*
// what courses can this user view LPRs for
$views = get_user_courses_bycap($USER->id, 'block/lpr:read', $accessinfo, true);
$can_view = !empty($views);

// what courses can this user create LPRs for
$writes = get_user_courses_bycap($USER->id, 'block/lpr:write', $accessinfo, true);
$can_write = !empty($writes);

// what courses can this user print LPRs for
$prints = get_user_courses_bycap($USER->id, 'block/lpr:print', $accessinfo, true);
$can_print = !empty($prints);
*/
?>