<?php 

/**
 * Creates an entry for an report 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//get the id of the course that is currently being used
$user_id = $PARSER->required_param('user_id', PARAM_INT);

//get the id of the course that is currently being used
$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);




// instantiate the db
$dbc = new ilp_db();


$plpuser	=	$dbc->get_user_by_id($user_id);

// setup the navigation breadcrumbs
//block name
$PAGE->navbar->add(get_string('ilpname', 'block_ilp'),null,'title');

//user intials
$PAGE->navbar->add(fullname($plpuser),null,'title');

//section name
$PAGE->navbar->add(get_string('dashboard','block_ilp'),null,'title');


// setup the page title and heading
$SITE	=	$dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp')." : ".fullname($plpuser));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-dashboard');
$PAGE->set_pagelayout('ilp');
$PAGE->set_url($CFG->wwwroot."/blocks/ilp/actions/view_main.php",$PARSER->get_params());

//get the enabled template
$temp	=	$dbc->get_enabled_template();

$classname	=	$temp->name;

//include the class file for the enabled template
require_once($CFG->dirroot."/blocks/ilp/classes/dashboard/templates/{$classname}.php");

$template	=	new $classname();

//check if the student has a user status record if not create one
if (!$dbc->get_user_status($user_id)) {
	//the user can not change there own status so we must set the modifying user to 
	//the default user
	$user_modified_id	=	($user_id != $USER->id) ? $USER->id : ILP_DEFAULT_USER_ID;
	$studentstatus	=	new stdClass();
	$studentstatus->user_id					=	$user_id;
	$studentstatus->user_modified_id		=	$user_modified_id;
	$studentstatus->parent_id				=	ILP_DEFAULT_USERSTATUS_RECORD;
	
	$dbc->create_userstatus($studentstatus);
}  

//require the view_main.html file
require_once($CFG->dirroot."/blocks/ilp/views/view_main.html");
?>
