<?php
/**
 * Performs the permissions checks on the current user
 * 
 * @copyright &copy; 2009 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lpr
 * @version 1.0 - MODIFIED 23/04/2013
 */

 
$userid     = optional_param('id', 0, PARAM_INT); // this is required
$courseid   = optional_param('courseid', SITEID, PARAM_INT); // this are required

global $DB;

if (!$userid) {
$userid = $USER->id ;
}
 
// get the contexts
$sitecontext = get_context_instance(CONTEXT_SYSTEM);
$usercontext = get_context_instance(CONTEXT_USER, $userid);


if ($courseid) {
	if (! $course = $DB->get_record('course', array('id'=>$courseid))) {
		//echo("DB");
		//print_object($DB);
		//print_error("Course ID is incorrect".$courseid);
	}
	if (! $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id)) {
		error("Context ID is incorrect");
	}
	$context = $coursecontext;
}


// we need to check the capabilities against all courses
if(isset($USER->access)) {
    $accessinfo = $USER->access;
} else {
    $accessinfo = $USER->access = get_user_access_sitewide($USER->id);
}

// what courses can this user drill down into?
$views = get_user_courses_bycap($USER->id, 'block/ilp:viewotherilp', $accessinfo, true);
$can_view = !empty($views);

?>