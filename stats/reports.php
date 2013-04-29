<?php
/**
 * Displays course categories and courses along with a statistical breakdown of
 * the performance of their learners based on the LPRs.
 *
 * @copyright &copy; 2009 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package LPR
 * @version 1.0 - MODIFIED
 */

// initialise moodle
require_once('../../../config.php');

// using these globals
global $SITE, $CFG, $USER, $DB;

// include the permissions check (uses ILP based permissions to see if they are a teacher)
require_once("access_content.php");

// include the permissions check
require_once("{$CFG->dirroot}/lib/moodlelib.php");

if(!$can_view) {
    error("You do not have permission to view reports");
}

// include the databse library
require_once("ilp_stats_db.php");

// instantiate the db wrapper
$ilp_db = new ilp_stats_db();

// fetch the optional filter params
//we will only ever show cateories here, courses will be shown by the ILP overview page that already exists!
$category_id = optional_param('category_id', null, PARAM_INT);

// if there is a category_id: fetch the category, or fail if the id is wrong
if (!empty($category_id) && ($category = $DB->get_record('course_categories', array('id'=> $category_id))) == false) {
    error("Category ID is incorrect");
}

// setup the navlinks, page heading and where conditions
$navlinks = array();
$heading = array();

if (!empty($category)) {

    $navlinks[] = array(
        'name' => get_string('categories'),
        'link' => $CFG->wwwroot.'/course/index.php'
    );

    $navlinks[] = array(
        'name' => $category->name,
        'link' => $CFG->wwwroot.'/course/category.php?id='.$category->id
    );
    $heading[] = $category->name;
}

$navlinks[] = array(
    'name' => get_string('ilp_stats_nav','block_ilp'),
    'link' => null
);

$navlinks = build_navigation($navlinks);

if(empty($heading)) {
    $heading[] = $SITE->shortname;
}

$heading = implode(' - ', $heading).' : '.get_string('ilp_stats_nav', 'block_ilp');

// print the theme's header
if (empty($course)) {
    print_header($heading, $heading, $navlinks);
} else {
    // filtering by a course should also display the course navigation menu
    print_header($heading, $heading, $navlinks, '', '', true, '&nbsp;', navmenu($course));
}

// print the page heading
print_heading($heading);


//actually do some work now after all that!




// print the footer
print_footer();
?>