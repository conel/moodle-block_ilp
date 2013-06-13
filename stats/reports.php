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

//if we are not at the top of the tree then a quick link to take us up a level would be good:

if ($category->id != 0) {
echo "<p class=\"up\"><a href=\"?category_id=" . $category->parent . "\">Go up a level</a></p>";
}

// load up the stats for this category! The amazing SQL stored procedure does everything so we just need to display stuff!
// put it all in an accordian so it can be expanded collapsed etc

echo "<div id=\"ilp_stats_accordian\">";

$results = $ilp_db->get_category_stats($category_id,1);
$courseresults = $ilp_db->get_course_stats($category_id,1);
if (!empty($results)) {
	$row = array_shift(array_values($results));
	}
else {
	$row = array_shift(array_values($courseresults));
	}
$curr = "";
if (!empty($row) and $row->term_start_date < time() and $row->term_end_date > time()) 
	{ $curr = " class=\"curr\" ";}

echo "<h3".$curr.">Term 1</h3>";
Echo "<div class=\"ilp_stats\">";
if (!empty($results)) {
	echo "<h4>Category stats</h4>";
	draw_table($results);
	echo "<p class=\"csv\"><a href=\"export.php?termnum=1\">Export all category stats for term 1 to CSV</a></p>";
	}
if (!empty($courseresults)) {
	echo "<h4>Course stats</h4>";
	draw_table($courseresults);
	}
Echo "</div>";

$results = $ilp_db->get_category_stats($category_id,2);
$courseresults = $ilp_db->get_course_stats($category_id,2);
if (!empty($results)) {
	$row = array_shift(array_values($results));
	}
else {
	$row = array_shift(array_values($courseresults));
	}
$curr = "";

if (!empty($row) and $row->term_start_date < time() and $row->term_end_date > time()) 
{ $curr = " class=\"curr\" ";}

echo "<h3".$curr.">Term 2</h3>";
Echo "<div class=\"ilp_stats\">";
if (!empty($results)) {
	echo "<h4>Category stats</h4>";
	draw_table($results);
	echo "<p class=\"csv\"><a href=\"export.php?termnum=2\">Export all category stats for term 2 to CSV</a></p>";
	}
if (!empty($courseresults)) {
	echo "<h4>Course stats</h4>";
	draw_table($courseresults);
	}
Echo "</div>";

$results = $ilp_db->get_category_stats($category_id,3);
$courseresults = $ilp_db->get_course_stats($category_id,3);
if (!empty($results)) {
	$row = array_shift(array_values($results));
	}
else {
	$row = array_shift(array_values($courseresults));
	}
$curr = "";

if (!empty($row) and $row->term_start_date < time() and $row->term_end_date > time()) 
{ $curr = " class=\"curr\" ";}

echo "<h3".$curr.">Term 3</h3>";
Echo "<div class=\"ilp_stats\">";
if (!empty($results)) {
	echo "<h4>Category stats</h4>";
	draw_table($results);
	echo "<p class=\"csv\"><a href=\"export.php?termnum=3\">Export all category stats for term 3 to CSV</a></p>";
	}
if (!empty($courseresults)) {
	echo "<h4>Course stats</h4>";
	draw_table($courseresults);
	}
Echo "</div>";

echo "</div>";

//print_object($results);
//print_object($category);

// print the footer
print_footer();
//include javascript to setup accordian and select correct term
echo "<script src=\"stats.js\"></script>";

function draw_table($results) {
	//updated to include totals . . 
	
	$students = 0;
	$green = 0;
	$amber = 0;
	$red = 0;
	$targets = 0;
	$targets_outstanding = 0;
	$tutor_review = 0;
	$good_performance = 0;
	$cause_for_concern = 0;
	$student_progress = 0;
	$disciplinary = 0;
	$target_grade = 0;
	
	if (!empty($results)) {

		Echo "<table><tr><th>Name</th><th>Students</th><th>Green</th><th>Amber</th><th>Red</th><th>Targets</th><th>Overdue Targets</th><th>Tutor Review</th><th>Good Performance</th><th>Cause for concern</th><th>Student progress</th><th>Disciplinary</th><th>Target grade</th></tr>";

		foreach ($results as $r) {
		
		$url = ($r->is_course == 0 ? "?category_id=".$r->id : "/blocks/ilp/actions/view_studentlist.php?tutor=0&course_id=".$r->id);
		
		
		echo "<tr><td><a href=\"".$url."\">".$r->name."</a></td><td>".$r->students."</td><td>".$r->green_status."</td><td>".$r->amber_status."</td><td>".$r->red_status."</td><td>".$r->target."</td><td>".$r->target_outstanding."</td><td>".$r->tutor_review."</td><td>".$r->good_perf_record."</td><td>".$r->cause_for_concern."</td><td>".$r->student_progress."</td><td>".$r->disciplinary."</td><td>".$r->target_grade."</td></tr>";

		$students = $students + $r->students;
		$green = $green + $r->green_status;
		$amber = $amber + $r->amber_status;
		$red = $red + $r->red_status;
		$targets = $targets + $r->target;
		$targets_outstanding = $targets_outstanding + $r->target_outstanding;
		$tutor_review = $tutor_review + $r->tutor_review;
		$good_performance = $good_performance + $r->good_perf_record;
		$cause_for_concern = $cause_for_concern + $r->cause_for_concern;
		$student_progress = $student_progress + $r->student_progress;
		$disciplinary = $disciplinary + $r->disciplinary;
		$target_grade = $target_grade + $r->target_grade;
		
		}
		echo "<tr class=\"totalrow\"><td>Totals</td><td>".$students."</td><td>".$green."</td><td>".$amber."</td><td>".$red."</td><td>".$targets."</td><td>".$targets_outstanding."</td><td>".$tutor_review."</td><td>".$good_performance."</td><td>".$cause_for_concern."</td><td>".$student_progress."</td><td>".$disciplinary."</td><td>".$target_grade."</td></tr>";
		echo "</table>";
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

        echo '<option '.$selected.' value="'.$category->id.'" >'.$indent.
             format_string($category->name, true, array('context' => $category->context)).'</option>';

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