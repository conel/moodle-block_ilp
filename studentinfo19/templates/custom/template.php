<?php 
include ('access_context.php'); 
require_once("{$CFG->dirroot}/blocks/ilp/studentinfo19/dbconnect.php"); 
require_once("{$CFG->dirroot}/blocks/ilp/studentinfo19/access_content.php");
require_once("{$CFG->dirroot}/blocks/ilp/studentinfo19/cache.class.php");
$query = "SELECT * FROM mdl_user where id = $userid";
global $DB;
if ($ausers = $DB->get_records_sql($query)) {
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
$html = "";
$html .= '
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
$baseurl = $CFG->wwwroot.'/blocks/bksb/initial_assessment.php?id='.$userid;

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
	

$html .= '<div id="bksb_ia_results">';
$html .= '<h4>Initial Assessments</h4>';
echo $html;
$html = "";
// Start working -- this is necessary as soon as the niceties are over
$table->setup();

$bksb_results = $bksb->getResults($conel_id);
$row = $bksb_results;
$table->add_data($row);

ob_start();
$table->print_html();  // Print the whole table
$ia_html = ob_get_contents();
ob_end_clean();

$html .= $ia_html;
$html .= '</div>';
$html .= '<div id="bksb_diag_results" class="stuinfobox">';
$html .= '<h4>Diagnostic Overviews</h4>';

//$baseurl = $CFG->wwwroot.'/blocks/bksb/bksb_diagnostic_overview.php?courseid='.$courseid;
$assessment_types = $bksb->ass_types;

$sessid = $bksb->getBksbSessionNo_bypass($conel_id);

//$html .= '<p><br /><a href="http://bksb/bksb_Reporting/Reports/DiagReport.aspx?session='.$sessid.'">View results in BKSB</a></p>';
//$html .= '<p><br /><a href="/blocks/bksb/diagnostic_assessment.php?id='.$userid.'&course_id='.$_GET['courseid'].'">View results in BKSB</a></p>';


$access_is_teacher = has_capability('block/bksb:view_all_results', $coursecontext);
$access_is_student = has_capability('block/bksb:view_own_results', $coursecontext);
$access_is_god = false;
if (has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) $access_is_god = true;

$bksb = new BksbReporting();
// Return from cache if set
    Cache1::init('user-'.$user->id.'-da-ilp-html.cache', $bksb->cache_life);
    if (Cache1::cacheFileExists()) {
        $diag_html = Cache1::getCache();
    } else {
        $best_scores = $bksb->getBestScores($conel_id);
        $user_sessions = $bksb->getBksbDiagSessions($conel_id);
        $existing_diagnostics = $bksb->filterAssessmentsFromSessions($user_sessions);

        $results_found = false;

        ob_start();
        echo $header;
        foreach ($existing_diagnostics as $ass_no => $ass_type) {

            $bksb_results = $bksb->getDiagnosticResults($conel_id, $ass_no, $best_scores);
            if ($bksb_results === false) continue;

            $results_found = true;

            print_heading('<span>'.$ass_type.'</span> Assessment');

            // Create array of questions for num returned
            $questions = range(1, count($bksb_results));
            // nkowald - 2010-10-05 - Add question % column
            $questions[] = 'BKSB %';
            
            $tablecolumns = $questions;
            $tableheaders = $questions;

            $table = new flexible_table('bksb_do');
                            
            $table->define_columns($tablecolumns);
            $table->define_headers($tableheaders);
            $table->define_baseurl($baseurl);
            $table->collapsible(false);
            $table->initialbars(false);
            $table->set_attribute('cellspacing', '0');
            $table->set_attribute('id', 'bksb_results_' . $ass_no);
            $table->set_attribute('class', 'bksb_results');
            $table->set_attribute('width', '95%');
            $table->set_attribute('align', 'center');
            foreach ($questions as $question) {
                $table->no_sorting($question);
            }
                
            $table->setup();
            $diag_results = array();
            foreach ($bksb_results as $res) {
                $diag_results[] = $bksb->getHTMLResult($res[1]);
            }
                
            $bksb_session_id = isset($user_sessions[$ass_type]) ? $user_sessions[$ass_type] : 0;
            $percentage = $bksb->getBksbPercentage($bksb_session_id);
            
            $bksb_results_url = 'http://bksb/bksb_Reporting/Reports/DiagReport.aspx?session='.$bksb_session_id;	
            $diag_results[] = '<span style="white-space:nowrap";>'.$percentage.'%<br /><a href="'.$bksb_results_url.'" class="percentage_link" title="Go to BKSB results page" target="_blank">View on BKSB</a></span>';
            
            $table->add_data($diag_results);

            $table->print_html();  // Print the table

            $overviews = $bksb->getAssDetails($ass_no);
            echo '<table class="bksb_key" width="95%">';
            echo '<tr><td>';

            echo "<h5>Questions</h5>";
            echo "<ol>";
            foreach ($overviews as $overview) {
                echo "<li>".$overview[0]."<span style=\"color:#CCC;\"> &mdash; ".$overview[1]."</span></li>";
            }
            echo "</ol>";
            echo '</td></tr>';
            echo '</table>';

        } // foreach

        if ($results_found == false) {
            echo '<center><p><b>No diagnostic overviews for this student.</b></p></center>';
        }
        echo '<br />';

        $diag_html = ob_get_contents();
        ob_end_clean();
        Cache1::setCache($diag_html);
    }

$html .= $diag_html;


// Predicted Functional Skills grades - Not working correctly

//$pstatus = $mis->Execute("SELECT COURSE_CODE,ACADEMIC_YEAR,MODULE_DESC,ENROL_STATUS FROM FES.MOODLE_CURRENT_ENROLMENTS WHERE ID = '381800'");
$pstatus = $mis->Execute("SELECT COURSE_CODE,ACADEMIC_YEAR,MODULE_DESC,ENROL_STATUS FROM FES.MOODLE_CURRENT_ENROLMENTS WHERE ID = '$user->idnumber'");

$mcodes = array('FS Maths' => 'Maths','FS English' => 'English','FS ICT' => 'ICT');

$stes = array();

if (!$pstatus) {print $mis->ErrorMsg();} else  
while (!$pstatus->EOF) {
	
	$mdesc = $pstatus->fields['MODULE_DESC'];
	
	if(isset($mcodes[$mdesc])) $stes[$mdesc] = $pstatus->fields['ENROL_STATUS'];
	
	$pstatus->MoveNext();
}
 
$fsrow = array();

$grades = array('P' => 'Pass','R' => 'At Risk','F'=>'Fail');

$i = 0;

foreach ($stes as $key => $value) {

	$fsrow[$i]['Unit'] = $key;
	$fsrow[$i]['Status'] = $value;
	
	//Functional Skills Level
	$poutcome = $mis->Execute("SELECT FS_LEVEL_DESC FROM FES.MOODLE_PREDICTED_OUTCOMES WHERE PERSON_CODE='$user->idnumber' AND OBJECT_TYPE='Functional Skills Level' AND FS_TYPE_DESC='$key'");	
	$fsrow[$i]['Level'] = $poutcome->fields['FS_LEVEL_DESC'];
	
	//Mock Results
	$poutcome = $mis->Execute("SELECT GRADE_DESC FROM FES.MOODLE_PREDICTED_OUTCOMES WHERE PERSON_CODE='$user->idnumber' AND OBJECT_TYPE='Mock Results' AND FS_TYPE_DESC='$key' ORDER BY REVIEW_NUMBER");
	$j=0;
	$mress = array('Dec','Feb');
	if (!$poutcome) {} else
	while (!$poutcome->EOF) {
		if(!isset($mress[$j]))break;		
		$fsrow[$i][$mress[$j]] = $grades[$poutcome->fields['GRADE']]; 
		$poutcome->MoveNext();
		$j++;
	}
	
	//Functional Skills Review
	$poutcome = $mis->Execute("SELECT GRADE,REVIEW_NUMBER FROM FES.MOODLE_PREDICTED_OUTCOMES WHERE PERSON_CODE='$user->idnumber' AND OBJECT_TYPE='Functional Skills Review' AND FS_TYPE_DESC='$key' ORDER BY REVIEW_NUMBER");
	
	$j=1;
	if (!$poutcome) {} else
	while (!$poutcome->EOF) {		
		$fsrow[$i][$j] = $grades[$poutcome->fields['GRADE']]; 
		$poutcome->MoveNext();
		$j++;
	}
	
	$i++;
}
$html .= '</div>';
/*
RPM commenting out predicted FS  grades as this needs more work before it is worth including.

$html .= '<div id="predicted_outcome" class="stuinfobox">';
$html .= '<h4>Predicted Functional Skills grades (TBC)</h4>';

$html .= '<table id="predicted-outcomeFS ICT" class="flexible bksb_results" cellspacing="0" align="center" width="90%">
<tbody>
<tr>
<th colspan="3" class="header c0" scope="col">&nbsp;<div class="commands"></div></th>
<th colspan="2" class="header c0" scope="col">Mocks<div class="commands"></div></th>
<th colspan="3" class="header c0" scope="col">Reviews<div class="commands"></div></th>
</tr>
<tr>
<th class="header c0" scope="col">Unit<div class="commands"></div></th>
<th class="header c1" scope="col">Status<div class="commands"></div></th>
<th class="header c2" scope="col">Level<div class="commands"></div></th>
<th class="header c3" scope="col">Dec<div class="commands"></div></th>
<th class="header c4" scope="col">Feb<div class="commands"></div></th>
<th class="header c5" scope="col">1<div class="commands"></div></th>
<th class="header c6" scope="col">2<div class="commands"></div></th>
<th class="header c7" scope="col">3<div class="commands"></div></th>
</tr>';

/* this part isnt working! 

$html .= '<tr class="r0">';

foreach($fsrow as $fsrr) {
	foreach($fsrr as $key => $value) {
		$html .= '<td class="cell c'.$key.'">'.$value.'</td>';
	}
}

$html .= '</tr>';

$html .= '</tbody>
</table>';
			
$html .= '</div>';
*/

echo $html;

//RPM 11-03-2013
//Target grade section - going to add in target grade exactly the same way as it is shown in ilp_dashboard_student_info_plugin.php

echo '<div id="stuinfotarget" class="ilp stuinfobox" style="width:50%;">';

require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/plugins/ilp_dashboard_student_info_plugin.php');

$dash = new ilp_dashboard_student_info_plugin($user->id);

$reports = $dash->dbc->get_reports(ILP_ENABLED);

$stuinfotargetgrade = "No Target Grade added";

if (!empty($reports) ) {
	foreach ($reports as $r) {
		if ($dash->dbc->has_plugin_field($r->id,'ilp_element_plugin_state')) {

			$reportinfo				=	new stdClass();
			$reportinfo->total		=	$dash->dbc->count_report_entries($r->id,$dash->student_id);
			$reportinfo->actual		=	$dash->dbc->count_report_entries_with_state($r->id,$dash->student_id,ILP_STATE_PASS);
			//retrieve the number of entries that have the not counted state
			$reportinfo->notcounted	=	$dash->dbc->count_report_entries_with_state($r->id,$dash->student_id,ILP_STATE_NOTCOUNTED);

			 //if total_possible is empty then there will be nothing to report
			if (!empty($reportinfo->total)) {
				$reportinfo->total     =   $reportinfo->total -  $reportinfo->notcounted;
				//calculate the percentage
				$reportinfo->percentage	=	$reportinfo->actual/$reportinfo->total	* 100;
			
				$reportinfo->name	=	$r->name;

				$percentagebars[]	=	$reportinfo;
			}
			
		}				
		
		
		if ($r->name == "Target Grade") {
			
			//RPM - another copy and paste from ilp_dashboard_reports_tab.php
			//works in the same way as the targets one but uses a custom html page to isplay them like the studnt progress one does.
			
			$reportentries	=	$dash->dbc->get_user_report_entries($r->id,$dash->student_id);
			$reportfields = $dash->dbc->get_report_fields_by_position($r->id);
			
			$access_report_editreports	= false;
			
			//start buffering output
			ob_start();
			
			$icon =	(!empty($r->binary_icon)) ? $CFG->wwwroot."/blocks/ilp/iconfile.php?report_id=".$r->id : $CFG->wwwroot."/blocks/ilp/pix/icons/defaultreport.gif";
		
			$icon = "<img id='reporticon' class='icon_med' alt='$r->name ".get_string('reports','block_ilp')."' src='$icon' />";
			
			//RPM - new function to draw add / edit button for this report summary if permission exists.
			$dash->addreportbutton($r);
			//RPM end
			
			echo "<h4>{$r->name}</h4>";
			
			//create the entries list var that will hold the entry information
			$entrieslist	=	array();

			if (!empty($reportentries)) {
				foreach ($reportentries as $entry)	{
				// RPM - need to change to only show the first record : $entry = $reportentries[0]; doesnt work, think it is a datarow

					//TODO: is there a better way of doing this?
					//I am currently looping through each of the fields in the report and get the data for it
					//by using the plugin class. I do this for two reasons it may lock the database for less time then
					//making a large sql query and 2 it will also allow for plugins which return multiple values. However
					//I am not naive enough to think there is not a better way!

					$entry_data	=	new stdClass();

					//get the creator of the entry
					$creator				=	$dash->dbc->get_user_by_id($entry->creator_id);

					//get comments for this entry
					$comments				=	$dash->dbc->get_entry_comments($entry->id);

					//
					$entry_data->creator		=	(!empty($creator)) ? fullname($creator)	: get_string('notfound','block_ilp');
					$entry_data->created		=	userdate($entry->timecreated);
					$entry_data->modified		=	userdate($entry->timemodified);
					$entry_data->user_id		=	$entry->user_id;
					$entry_data->entry_id		=	$entry->id;

					//does this report allow users to say it is related to a particular course
					$has_courserelated	=	(!$dash->dbc->has_plugin_field($r->id,'ilp_element_plugin_course')) ? false : true;

					/*
					//doesn't have course related features but this could be reinstated later if required
					if (!empty($has_courserelated))	{
						$courserelated	=	$this->dbc->has_plugin_field($r->id,'ilp_element_plugin_course');
						//the should not be anymore than one of these fields in a report
						foreach ($courserelated as $cr) {
								$dontdisplay[] 	=	$cr->id;
								$courserelatedfield_id	=	$cr->id;
						}
					}
					
					if ($has_courserelated) {
						$coursename	=	false;
						$crfield	=	$this->dbc->get_report_coursefield($entry->id,$courserelatedfield_id);
						if (empty($crfield) || empty($crfield->value)) {
							$coursename	=	get_string('allcourses','block_ilp');
						} else if ($crfield->value == '-1') {
							$coursename	=	get_string('personal','block_ilp');
						} else {
							$crc	=	$this->dbc->get_course_by_id($crfield->value);
							if (!empty($crc)) $coursename	=	$crc->shortname;
						}
						$entry_data->coursename = (!empty($coursename)) ? $coursename : '';
					}
					*/
					
					foreach ($reportfields as $field) {

						//get the plugin record that for the plugin
						$pluginrecord	=	$dash->dbc->get_plugin_by_id($field->plugin_id);

						//take the name field from the plugin as it will be used to call the instantiate the plugin class
						$classname = $pluginrecord->name;

						// include the class for the plugin
						include_once("{$CFG->dirroot}/blocks/ilp/classes/form_elements/plugins/{$classname}.php");

						if(!class_exists($classname)) {
							print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
						}

						//instantiate the plugin class
						$pluginclass	=	new $classname();

						if ($pluginclass->is_viewable() != false)	{
							$pluginclass->load($field->id);

							//call the plugin class entry data method
							$pluginclass->view_data($field->id,$entry->id,$entry_data);
						} else	{
							$dontdisplay[]	=	$field->id;
						}

					}

					include($CFG->dirroot.'/blocks/ilp/classes/dashboard/plugins/ilp_dashboard_target_grades.html');

				}
			} else {

				echo get_string('nothingtodisplay');

			}				

			$stuinfotargetgrade = ob_get_contents();
			ob_end_clean();
		}
		
		//RPM End
	}
}
echo $stuinfotargetgrade;



echo '</div></div>';



?>
