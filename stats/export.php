<?php


// initialise moodle
require_once('../../../config.php');

// using these globals
global $SITE, $CFG, $USER, $DB;

// include the permissions check (uses ILP based permissions to see if they are a teacher)
require_once("access_content.php");

if(!$can_view) {
    error("You do not have permission to export reports");
}

// include the databse library
require_once("ilp_stats_db.php");

// instantiate the db wrapper
$ilp_db = new ilp_stats_db();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment;filename="export.csv"');
header('Cache-Control: max-age=0');
// See: http://es2.php.net/manual/en/wrappers.php.php
$fpcsv = fopen('php://output', "a+");

$exportcsv_q = $ilp_db->get_all_stats($_GET['termnum']);
//get the keys as the first row
$row = array_shift(array_values($exportcsv_q));

fputcsv($fpcsv, array('Category ID', 'Name', 'Parent', 'Term', 'Term Start', 'Term End', 'Category ID', 'Students', 'Red Status', 'Amber Status', 'Green Status', 'Last Updated', 'Term ID', 'Targets', 'Targets Outstanding', 'Tutor Reviews', 'Good Performance Records', 'Cause for concern', 'Student progress', 'Disciplinary', 'Target Grade'));


foreach ($exportcsv_q as $r) {
	$array =  (array) $r;
	fputcsv($fpcsv, $array);
}
exit;

/*
if (@mysql_num_rows($exportcsv_q) > 0) {
    $campos = mysql_num_fields($exportcsv_q);
    while ($exportcsv_r = mysql_fetch_row($exportcsv_q)) {
         fputcsv($fpcsv, $exportcsv_r);
    }
}
exit;

*/

?>