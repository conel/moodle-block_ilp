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

$PAGE->requires->css_theme(new moodle_url('../styles.css'));

// print the theme's header
if (empty($course)) {
    print_header($heading, $heading, $navlinks);
} else {
    // filtering by a course should also display the course navigation menu
    print_header($heading, $heading, $navlinks, '', '', true, '&nbsp;', navmenu($course));
}


//actually do some work now after all that!

//print our selector
echo '<form name="cat" id="cat" method="get"><select name="category_id" onchange="this.form.submit();">';
echo '<option value="0">Overview</option>';
print_category_option(NULL,$category->id);
echo '</select></form>';
// print the page heading
print_heading($heading);

// load up the stats for this category! The amazing SQL stored procedure does everything so we just need to display stuff!
// put it all in an accordian so it can be expanded collapsed etc



echo "<div id=\"ilp_stats_accordian\">";

$results = $ilp_db->get_category_stats($category_id,1);
$row = array_shift($results);
$curr = "";

if ($row->term_start_date < time() and $row->term_end_date > time()) 
	{ $curr = " class=\"curr\" ";}

echo "<h3".$curr.">Term 1</h3>";
draw_table($results);


$results = $ilp_db->get_category_stats($category_id,2);
$row = array_shift($results);
$curr = "";

if ($row->term_start_date < time() and $row->term_end_date > time()) 
{ $curr = " class=\"curr\" ";}

echo "<h3".$curr.">Term 2</h3>";
draw_table($results);

$results = $ilp_db->get_category_stats($category_id,3);
$row = array_shift($results);
$curr = "";

if ($row->t.term_start_date < time() and $row->term_end_date > time()) 
{ $curr = " class=\"curr\" ";}

echo "<h3".$curr.">Term 3</h3>";
draw_table($results);

echo "</div>";

//print_object($results);
//print_object($category);

// print the footer
print_footer();
//include javascript to setup accordian and select correct term
echo "<script src=\"stats.js\"></script>";

function draw_table($results) {

	if (!empty($results)) {

		Echo "<div class=\"ilp_stats\"><table><tr><th>Name</th><th>Students</th><th>Green</th><th>Amber</th><th>Red</th><th>Targets</th><th>Overdue Targets</th><th>Tutor Review</th><th>Good Performance</th><th>Cause for concern</th><th>Student progress</th><th>Disciplinary</th><th>Target grade</th></tr>";

		foreach ($results as $r) {
		
		$url = ($r->coursecount == 0 ? "?category_id=".$r->id : "/course/category.php?id=".$r->id);
		
		echo "<tr><td><a href=\"".$url."\">".$r->name."</a></td><td>".$r->students."</td><td>".$r->green_status."</td><td>".$r->amber_status."</td><td>".$r->red_status."</td><td>".$r->target."</td><td>".$r->target_outstanding."</td><td>".$r->tutor_review."</td><td>".$r->good_perf_record."</td><td>".$r->cause_for_concern."</td><td>".$r->student_progress."</td><td>".$r->disciplinary."</td><td>".$r->target_grade."</td></tr>";

		}

		echo "</table></div>";
	}
}

function print_category_option($category, $selcat, $depth=-1) {

//recursive function based on course/index.php method, idea is to go through all categories and build a Select list that can be used to choose which category we want to display stats for

global $CFG, $USER, $OUTPUT;

    static $str = NULL;

    if (!empty($category)) {

        if (!isset($category->context)) {
            $category->context = context_coursecat::instance($category->id);
        }

		$indent = '';
		$selected = ($category->id == $selcat ? ' selected="selected" ' : '');
		
        for ($i=0; $i<$depth;$i++) {
            $indent .= '&nbsp;&nbsp;&nbsp;';
        }

		if ($category->coursecount == 0) {
        echo '<option '.$selected.' value="'.$category->id.'" >'.$indent.
             format_string($category->name, true, array('context' => $category->context)).'</option>';
			 }

    } else {
        $category = new stdClass();
        $category->id = '0';
    }

    if ($categories = get_categories($category->id)) {   // Print all the children recursively
        $countcats = count($categories);
        $count = 0;
        $first = true;
        $last = false;
        foreach ($categories as $cat) {
            $count++;
            if ($count == $countcats) {
                $last = true;
            }
            $up = $first ? false : true;
            $down = $last ? false : true;
            $first = false;

            print_category_option($cat, $selcat, $depth+1);
        }
    }

}
?>