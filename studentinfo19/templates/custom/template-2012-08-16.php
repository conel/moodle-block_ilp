<?php 
include ('access_context.php'); 
require_once($CFG->dirroot.'/blocks/ilp/templates/custom/dbconnect.php'); 

$query = "SELECT * FROM mdl_user where id = $userid";
if ($ausers = get_records_sql($query)) {
	foreach ($ausers as $auser) {
		$conel_id = $auser->idnumber;
	}
}

//$details = $mis->Execute('SELECT * FROM FES.MOODLE_PEOPLE WHERE to_char("STUDENT_ID") = to_char('.$user->idnumber.')');
$details = $mis->Execute("SELECT * FROM FES.MOODLE_PEOPLE WHERE STUDENT_ID = '$user->idnumber'");

// print some personal info about student.
$age = ($details->fields['AGE'] != '') ? '&nbsp;&nbsp;(Age:'.$details->fields['AGE'].')' : '';

// Clean up details. If blank output emdash
foreach ($details->fields as $key => $value) {
	// ignore these keys
	$ignore_keys = array('ADDRESS_LINE_1', 'ADDRESS_LINE_2', 'ADDRESS_LINE_3', 'TOWN', 'REGION', 'POSTCODE');
	if ($value == '' && !in_array($key, $ignore_keys)) {
		$details->fields[$key] = '&ndash;';
	}
}

$html = '
<div id="student_profile">
<h2>'.$user->firstname.' '.$user->lastname.'</h2>
<div class="student_personal_details">
<table width="100%">
	<tr>
		<td align="center" valign="top" width="115" class="userpic"><a href="'.$CFG->wwwroot.'/user/view.php?id='.$userid.'&course='.$courseid.'"><img src="'.$CFG->wwwroot.'/user/pix.php/'.$userid.'/f1.jpg" class="reflect" width="100" height="100" alt="" border="0" /></a></td>
		<td valign="top">
			<div class="spd_personal">
				<h4>Personal</h4>
				<table>
					<tr><td class="label">Email address:</td><td>'.$details->fields['EMAIL'].'</td></tr>
					<tr><td class="label">Student ID:</td><td>'.$details->fields['STUDENT_ID'].'</td></tr>
					<tr><td class="label">Phone:</td><td>'.$details->fields['TELEPHONE'].'</td></tr>
					<tr><td class="label">Mobile:</td><td>'.$details->fields['MOBILE_PHONE_NUMBER'].'</td></tr>
					<!--<tr><td class="label">Emergency contact:</td><td></td></tr>
					<tr><td class="label">Employer contact:</td><td></td></tr>-->
					<tr><td class="label">Gender:</td><td>'.$details->fields['GENDER'].'</td></tr>
					<tr><td class="label">Date of Birth:</td><td>'.$details->fields['DATE_OF_BIRTH']. $age . '</td></tr>
					<tr><td class="label">Next of Kin:</td><td>'.$details->fields['NEXT_OF_KIN'].'</td></tr>
					<tr><td class="label">Next of Kin Contact:</td><td>'.$details->fields['NOK_CONTACT_NO'].'</td></tr>
					<tr><td class="label">EMA:</td><td>'.$details->fields['EMA_STATUS'].'</td></tr>
				</table>
			</div>
			<div class="spd_address">
				<h4>Address</h4>';
				$address = '<table><tr><td>';
				// Format address 
				$address .= ($details->fields['ADDRESS_LINE_1'] != '') ? $details->fields['ADDRESS_LINE_1'] . "<br />" : '';
				$address .= ($details->fields['ADDRESS_LINE_2'] != '') ? $details->fields['ADDRESS_LINE_2'] . "<br />" : '';
				$address .= ($details->fields['ADDRESS_LINE_3'] != '') ? $details->fields['ADDRESS_LINE_3'] . "<br />" : '';
				$address .= ($details->fields['TOWN'] != '') ? $details->fields['TOWN'] . "<br />" : '';
				$address .= ($details->fields['REGION'] != '') ? $details->fields['REGION'] . "<br />" : '';
				$address .= ($details->fields['POST_CODE'] != '') ? $details->fields['POST_CODE'] : '';
				
				// Create Google Map link
				// nkowald - 2011-06-16 - Visible only for admins as it's scary
				if ($USER->id == 16772) {
					$add_1 = ($details->fields['ADDRESS_LINE_1'] != '') ? (str_replace(' ','+', $details->fields['ADDRESS_LINE_1'])) . "+"  : '';
					$add_2 = ($details->fields['ADDRESS_LINE_2'] != '') ? (str_replace(' ','+', $details->fields['ADDRESS_LINE_2'])) . "+"  : '';
					$add_3 = ($details->fields['ADDRESS_LINE_3'] != '') ? (str_replace(' ','+', $details->fields['ADDRESS_LINE_3'])) . "+" : '';
					$add_4 = ($details->fields['TOWN'] != '') ? (str_replace(' ','+', $details->fields['TOWN'])) . "+" : '';
					$add_5 = ($details->fields['REGION'] != '') ? (str_replace(' ','+', $details->fields['REGION'])) . "+" : '';
					$add_6 = ($details->fields['POST_CODE'] != '') ? (str_replace(' ','+', $details->fields['POST_CODE'])) : '';
					$gmap_add = $add_1 . $add_2 . $add_3 . $add_4 . $add_5 . $add_6;
					
					if ($gmap_add != '') {
						$google_map_link = '<br /><a href="http://maps.google.co.uk/maps?q='.$gmap_add.'&layer=c&ie=UTF8&iwloc=A" target="_blank">Google Map</a>';
					}
					$address .= $google_map_link;
				}
				
				$address .= '</td></tr></table>';
				$html .= $address;
			$html .= '</div>
		</td>
	</tr>
</table>
<div class="spd_entry_qual">
	<h4>Entry Qualifications</h4>';
	
	if (count($user_quals) > 0) {
		$i = 0;
		$html .= '<table><tr><th>Award Title</th><th>Qualification Type</th><th>Result/Grade</th><th>Awarding Body</th><th>Date</th></tr>';
		
		foreach ($user_quals as $key => $value) {
			$html .= '<tr>';
			$html .= '<td width="220">'.$user_quals[$i]['award_title'].'</td>';
			$html .= '<td>'.$user_quals[$i]['qual_desc'].' ('.$user_quals[$i]['qual_type'].')</td>';
			$html .= '<td>'.$user_quals[$i]['grade'].'</td>';
			$html .= '<td>'.$user_quals[$i]['awarding_body'].'</td>';
			$html .= '<td>'.$user_quals[$i]['achieved_year'].'</td>';
			$html .= '</tr>';
			$i++;
		}
		$html .='</table>';
	} else {
		$html .= '<table><tr><td>Entry qualifications not found for this user.</td></tr></table>';
	}
	
$html .= '</div></div>';

// Get BKSB Result categories
$cats = $bksb->ass_cats;
$tablecolumns = $cats;
$tableheaders = $cats;
$baseurl = $CFG->wwwroot.'/blocks/bksb/bksb_initial_assessment.php?userid='.$userid;

require_once($CFG->libdir.'/tablelib.php');
$table = new flexible_table('mod-targets');
				
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($baseurl);
$table->collapsible(false);
$table->initialbars(false);
$table->column_suppress('picture');	
$table->column_class('picture', 'picture');
$table->column_class('fullname', 'fullname');
$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'bksb_results_group');
$table->set_attribute('class', 'bksb_results');
$table->set_attribute('width', '90%');
$table->set_attribute('align', 'center');
foreach($cats as $cat) {
	$table->no_sorting($cat);
}
	
// Start working -- this is necessary as soon as the niceties are over
$table->setup();

$bksb_results = $bksb->getResults($conel_id);
$row = $bksb_results;
$table->add_data($row);
ob_start();
$table->print_html();  // Print the whole table
$ia_html = ob_get_contents();
ob_end_clean();
$html .= '<div id="bksb_ia_results">';
$html .= '<h4>Initial Assessments</h4>';
$html .= $ia_html;
$html .= '</div>';

$html .= '<div id="bksb_diag_results">';
$html .= '<h4>Diagnostic Overviews</h4>';

//$baseurl = $CFG->wwwroot.'/blocks/bksb/bksb_diagnostic_overview.php?courseid='.$courseid;
$assessment_types = $bksb->ass_types;

$results_found = false;
foreach ($assessment_types as $key => $value) {

	$ass_type = $bksb->ass_types[$key];
	$bksb_results = $bksb->getDiagnosticResults($conel_id, $key);

	// Check if bksb_results are blank
	$results = true;
	if (!in_array('X', $bksb_results) && !in_array('P', $bksb_results)) {
	   $results = false; 
	} else {
		$results = true;
		$results_found = true;
	}

	if ($results) {

		$diag_html .= "<h5>$ass_type Assessment</h5>";

		$no_questions = $bksb->getNoQuestions($key);

		$questions = array();

		// Create array of questions for num returned
		for ($i=1; $i<=$no_questions; $i++) {
		   $questions[] = $i; 
		}
		// nkowald - 2010-10-05 - Add question % column
		$questions[] = 'BKSB %';
		
		$tablecolumns = $questions;
		$tableheaders = $questions;

		$table = new flexible_table('mod-targets');
						
		$table->define_columns($tablecolumns);
		$table->define_headers($tableheaders);
		$table->define_baseurl($baseurl);
		$table->collapsible(false);
		$table->initialbars(false);
		$table->set_attribute('cellspacing', '0');
		$table->set_attribute('id', 'bksb_results_' . $key);
		$table->set_attribute('class', 'bksb_results');
		$table->set_attribute('width', '90%');
		$table->set_attribute('align', 'center');
		foreach ($questions as $question) {
			$table->no_sorting($question);
		}
			
		// Start working -- this is necessary as soon as the niceties are over
		$table->setup();
			
		// Change the colour of our P to green (add food colouring?)
		$bksb_results = str_replace('P', '<span class="bksb_passed">P</span>', $bksb_results);
		
		$bksb_results = str_replace('Tick', '<img src="'.$CFG->wwwroot.'/blocks/bksb/tick.png" alt="passed" width="20" height="19" />', $bksb_results);

		// nkowald - 2010-10-05 - Convert 'X' to an icon
		$bksb_results = str_replace('X', '<img src="'.$CFG->wwwroot.'/blocks/bksb/red-x.gif" alt="Not Yet Passed" width="15" height="15" />', $bksb_results);
		
		$percentage = ($bksb->getBksbPercentage($conel_id, $key)) ? $bksb->getBksbPercentage($conel_id, $key) : '-';
		
		$bksb_session_no = $bksb->getBksbSessionNo($conel_id, $key);
		$bksb_results_url = 'http://bksb/bksb_Reporting/Reports/DiagReport.aspx?session='.$bksb_session_no;	
		$bksb_results[] = '<a href="'.$bksb_results_url.'" class="percentage_link" title="Go to BKSB results page" target="_blank">'.$percentage.'%</a>';
		
		$table->add_data($bksb_results);

		ob_start();
		$table->print_html();  // Print the whole table
		$diag_html .= ob_get_contents();
		ob_end_clean();

		$overviews = $bksb->getAssDetails($key);
		$diag_html .= '<table class="bksb_key">';
		$diag_html .= '<tr><td>';
		$diag_html .= "<h5>Questions</h5>";
		$diag_html .= "<ol>";
		foreach ($overviews as $overview) {
			if ($overview[0] != $overview[1]) {
				$diag_html .= "<li>".$overview[0]."<span style=\"color:#CCC;\"> &mdash; ".$overview[1]."</span></li>";
			} else {
				$diag_html .= "<li>".$overview[0]."</li>";
			}
		}

		$diag_html .= "</ol>";
		$diag_html .= '</td></tr>';
		$diag_html .= '</table>';
	}

}
if ($results_found == false) {
	$diag_html .= '<table><tr><td><b>No diagnostic overviews for this student.</b></td></tr></table>';
}

$html .= $diag_html;

$html .= '</div>';

echo $html;

echo '</div>';

// Not sure if this is needed, will restore if so

if($config->ilp_show_student_info == '1' && ($view == 'info' || $view == 'all')) {
    echo '<div class="generalbox" id="ilp-student_info-overview">'; 
    display_ilp_student_info($user->id,$courseid); 
    echo '</div>';
}

if($config->ilp_show_personal_reports == 1 && ($view == 'personal' || $view == 'all')) { 
    echo '<div class="generalbox" id="ilp-personal_report-overview">';
    display_ilp_personal_report($user->id,$courseid);
    echo '</div>';
}
 
if($config->ilp_show_subject_reports == 1 && ($view == 'subject' || $view == 'all')) {
    echo '<div class="generalbox" id="ilp-subject_report-overview">';
    display_ilp_subject_report($user->id,$courseid);
    echo '</div>';  
}


?>
