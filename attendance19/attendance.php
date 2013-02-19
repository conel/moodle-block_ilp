<?php

	require_once('../../../config.php');
	require_once('block_ilp_lib.php');
	require_once('access_context.php');

    require_once('AttendancePunctuality.class.php');
    $attpunc = new AttendancePunctuality();

	global $GFG, $USER, $DB;

	$contextid    	= optional_param('contextid', 0, PARAM_INT);               // one of this or
	$courseid     	= optional_param('courseid', SITEID, PARAM_INT);          // this are required
	$group 			= optional_param('group', -1, PARAM_INT);
	$updatepref 	= optional_param('updatepref', -1, PARAM_INT);
	$userid 		= optional_param('userid', 0, PARAM_INT);
	$user 			= $DB->get_record('user', array('id'=>$userid));

	//$coursecontext ;
	if ($contextid) {
		if (! $coursecontext = get_context_instance_by_id($contextid)) {
			error("Context ID is incorrect");
		}
		if (! $course = get_record('course', 'id', $coursecontext->instanceid)) {
			error("Course ID is incorrect");
		}
	} else if ($courseid) {
		if (! $course = $DB->get_record('course', array('id'=>$courseid))) {
			error("Course ID is incorrect");
		}
		if (! $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id)) {
			error("Context ID is incorrect");
		}
	}
	// nkowald - 2010-09-27 - Updated this call as "id" does not hold courseid, seems course does
	//if (!$cm = get_record("course_modules", "id", $courseid)) {
	if (!$cm = $DB->get_records("course_modules", array("course"=>$courseid))) {
		error("Course Module ID was incorrect");
	}

	require_login($course);
	$sitecontext = get_context_instance(CONTEXT_SYSTEM);
	
	$this_page = ($_SERVER['REQUEST_URI'] != '') ? $_SERVER['REQUEST_URI'] : 'attendance.php';
    add_to_log($courseid, "ilp", "view attendance", $this_page, $userid);

	/* RPM
	if (has_capability('moodle/site:doanything',$sitecontext)) {  // are we god ?
		$access_isgod = 1 ;
	}
	*/
	
	if (has_capability('block/ilp:viewotherilp',$coursecontext)) { // are we the teacher on the course ?
		$access_isteacher = 1 ;
	}
	
	// If student is accessing this page, they may only view their attendance details!
	$staff_or_stud = 5;//get_role_staff_or_student($USER->id);
	// if student, check that userid == $USER->id
	if ($staff_or_stud == 5) {
		if ($userid != $USER->id) {
			//RPM Commented out to be able to view attendance in ILP - must check based on email address format for get_role_staff_or_student
			//error("You cannot view another user's attendance", $CFG->wwwroot . "/blocks/ilp/view.php");
		}
	}
	
	$strilp = get_string("ilp", "block_ilp");
    $strtarget = '';
	$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> <a href=\"../../blocks/ilp/view.php?courseid=$course->id&amp;id=$user->id\">$strilp</a>";		
	//RPM Temp
	//deprecated function see http://docs.moodle.org/dev/Migrating_your_code_to_the_2.0_rendering_API#print_header
	/*
	$navlinks = array(array('name'=>$strparticipants, 'link'=>$CFG->wwwroot.'/user/index.php?id='.$courseid, 'type'=>'misc'),
                  array('name'=>$strgroups, 'link'=>'', 'type'=>'misc'));
	$navigation = build_navigation($navlinks);
	print_header(get_string('publishcourse'), $course->fullname, $navigation,'',$meta, true, '&nbsp;', user_login_string($course, $USER));
	*/
	
	$navlinks = array(array('name'=>''.fullname($user).'', 'link'=>'', 'type'=>'misc'),
                  array('name'=>'ILP', 'link'=>'../actions/view_main.php?user_id='.$user->id.'&course_id='.$course->id.'', 'type'=>'misc'),array('name'=>'Attendance Detail', 'link'=>'', 'type'=>'misc'));
    $navigation = build_navigation($navlinks);
	print_header("Attendance: ".fullname($user)."", $course->fullname, $navigation,'',$meta, true, '&nbsp;', user_login_string($course, $USER));
	
	//print_header("Attendance: ".fullname($user)."", "$course->fullname","$navigation -> Attendance -> ".fullname($user)."","", "", true, navmenu($course, $cm));
	
	$page    	= optional_param('page', 0, PARAM_INT);
	$groupmode    = groups_get_course_groupmode($course);   // Groups are being used
	$currentgroup = groups_get_course_group($course, true);

	if (!$currentgroup) {      // To make some other functions work better later
		$currentgroup  = NULL;
	}

	$isseparategroups = ($course->groupmode == SEPARATEGROUPS and $course->groupmodeforce and !has_capability('moodle/site:accessallgroups', $context));	
	$doanythingroles = get_roles_with_capability('moodle/site:doanything', CAP_ALLOW, $sitecontext);

?>

<script type="text/javascript">
jQuery(document).ready(function(){  
    
	jQuery('#register_key').hide();
	
	jQuery('#show_key').click(function(event) {
        event.preventDefault();
        if (jQuery('#show_key').html() == 'Show key') {
            jQuery('#show_key').html('Hide key');
        } else {
            jQuery('#show_key').html('Show key');
        }
        jQuery('#register_key').slideToggle();
    });
	
	jQuery('#show_term_1').click(function(event) {
        event.preventDefault();
        if (jQuery('#show_term_1').html() == 'Show') {
            jQuery('#show_term_1').html('Hide');
        } else {
            jQuery('#show_term_1').html('Show');
        }
        jQuery('#term1').slideToggle();
    });
	
	jQuery('#show_term_2').click(function(event) {
        event.preventDefault();
        if (jQuery('#show_term_2').html() == 'Show') {
            jQuery('#show_term_2').html('Hide');
        } else {
            jQuery('#show_term_2').html('Show');
        }
        jQuery('#term2').slideToggle();
    });
	
});
</script>
<?php
        echo '<div class="generalbox" id="ilp-attendance-overview">';
        echo '<h1>Attendance</h1><br />';

        $att_shown = FALSE;

        echo '<a href="#" id="show_key">Show key</a>';
        echo '<div id="register_key">';
        echo '<strong style="font-weight:bold;">Register Marks Key</strong><br />';
        echo '<table id="att_key"><tr>';
        ksort($attpunc->marks_key);
        foreach ($attpunc->marks_key as $key => $value) {
            echo '<th class="key">'. $key . '</th>';
        }
        echo '</tr><tr>';
        foreach ($attpunc->marks_key as $key => $value) {
            echo '<td>'. $value . '</td>';
        }
        echo '</tr>';
        echo '</table>';
        echo '</div>';

//$no_terms = 3;
// nkowald - This value retrieved from the current term number
$no_terms = $attpunc->getCurrentTermNo();
for ($i=1; $i <= $no_terms; $i++) {
    
    if ($att_punct = $attpunc->getAttendancePunctuality($user->idnumber, $i)) {
		
        $att_shown = TRUE;
        $term = $i;
        $reg_weeks = $attpunc->getRegisterWeeks($user->idnumber, $term);
        $no_weeks = count($reg_weeks);
        
        // Get term dates of current term
        $term_dates = $attpunc->getCurrentTermDates();
        $t_start = date('d/m/Y', $term_dates[$i]['start']);
        $t_end = date('d/m/Y', $term_dates[$i]['end']);
        echo "<h2>Term $i: &nbsp; $t_start - $t_end &nbsp;&nbsp;";
		if ($i != $no_terms) {
			echo "(<a href=\"#\" id=\"show_term_$i\">Show</a>)";
		}
		echo "</h2>";
		
		if ($i != $no_terms) {
			echo "<div id=\"term$i\" style=\"display:none;\">";
		} else {
			echo "<div id=\"term$i\">";
		}
        echo '<table class="attendance">';
        echo '<tr>';
        echo '<th colspan="11">&nbsp;</th>';
        echo '<th colspan="'.$no_weeks.'" class="ws">Week Starting</th>';
        echo '</tr>';
        echo '<tr class="colheaders">';
        echo '<th scope="col" colspan="2">Module - Description</th>';
        echo '<th scope="col">Day</th>';
        echo '<th scope="col">Start</th>';
        echo '<th scope="col">End</th>';
        echo '<th scope="col">Attendance</th>';
        echo '<th scope="col">Present</th>';
        echo '<th scope="col">Absent</th>';
        echo '<th scope="col">Punctuality</th>';
        echo '<th scope="col">On time</th>';
        echo '<th scope="col">Late</th>';
        if ($no_weeks > 0) {
            foreach ($reg_weeks as $date) {
                $form_date = substr($date, 0, 5);
                //echo '<th>'.$form_date.'</th>';
                echo '<th><img src="vertical-date.php?text='.$date.'" width="10" height="45" alt="'.$date.'" /></th>';
            }
        }
        echo '</tr>';

        foreach($att_punct as $key => $atp) {

            echo '<tr>';

            unset($att_data);
            if ($atp['attendance'] != '') {
                $att_data   = $attpunc->getAttPuncData($atp['attendance']);
            }
            $colour     = (isset($att_data['colour'])) ? $att_data['colour'] : '';
            $att_class  = ($colour != '') ? ' ' . $colour : '';
            $att_fmt    = (isset($att_data['formatted'])) ? $att_data['formatted'] : '';

            if ($colour != '') {
                echo '<td class="attendance-'.$colour.'">&nbsp;</td>';
            } else {
                echo '<td class="attendance">&nbsp;</td>';
            }

            unset($punc_data);
            if ($atp['punctuality'] != '') {
                $punc_data  = $attpunc->getAttPuncData($atp['punctuality']);
            }
            $colour     = (isset($punc_data['colour'])) ? $punc_data['colour'] : '';
            $punc_class = ($colour != '') ? ' ' . $colour : '';
            $punc_fmt   = (isset($punc_data['formatted'])) ? $punc_data['formatted'] : '';


            echo '<td style="white-space:nowrap; "><span style="color:#000;">'.$key.'</span><br />
            '.$atp['module_desc'].'</td>';
            echo '<td class="center">'.$atp['day'].'</td>';
            echo '<td class="center">'.$atp['start_time'].'</td>';
            echo '<td class="center">'.$atp['end_time'].'</td>';
            echo '<td class="center'.$att_class.'">'.$att_fmt.'</td>';
            echo '<td class="center">'.$atp["sessions_present"].'</td>';
            echo '<td class="center">'.$atp["sessions_absent"].'</td>';
            echo '<td class="center'.$punc_class.'">'.$punc_fmt.'</td>';
            echo '<td class="center">'.$atp["sessions_on_time"].'</td>';
            echo '<td class="center">'.$atp["sessions_late"].'</td>';

            // Grab register marks for this module
            foreach ($reg_weeks as $week) {
                $start = $atp['start_time'];
                $end = $atp['end_time'];
                $day_num = $atp['day_num'];

                $week_parts = explode('/', $week);
                $wk_day = $week_parts[0];
                $wk_month = $week_parts[1];
                $wk_year = $week_parts[2];
                $week_start = mktime(0,0,0, $wk_month, $wk_day, $wk_year, 0);

				// What day number is week start date? Needed to work out the correct REG_DATE, based on week start date and REG_DAY_NUM
				$week_start_day_num = date('N', $week_start);
				
				if ($day_num > 1) {
                    // Day num holds the (1-7, Mon being 1, Tues 2 etc.)
                    // We find the date of the register mark we want by using this formula: week start date + (day num - week start day num) days
					
					// Example: (day num = 3 (Wed) so to find correct unixdate we do 3 - 1 (1 is the week day number so 1 = Monday, term 2 might start on a Tuesday)
					// (3 - 1 = 2) So we go what is the date 2 days ahead of week start date.
					
                    $day = $day_num - $week_start_day_num;
                    $unixdate = strtotime("+$day days", $week_start);
                    // convert to dd/mm/yyyy format for the method
                    $date = date('d/m/Y', $unixdate);
                } else {
                    $date = $week;
                    $unixdate = $week_start;
                }

                // What's the unixtime now?
                $unixtime_now = time();
                $register_id = $atp['register_id'];
				
				if (($unixtime_now > $unixdate) && $mark = $attpunc->getMarkForModuleSlot($user->idnumber, $term, $register_id, $date)) {
                    // Check for mark key and if found wrap it in a span with title for on hovers
                    if (isset($attpunc->marks_key[$mark])) {
						$class = ($mark == 'O') ? $class="center absent" : $class="center";
                        echo "<td class=\"$class\"><span class=\"hover\" title=\"".$attpunc->marks_key[$mark]."\">$mark</span></td>"; 
                    } else {
                        echo "<td class=\"center\">$mark</td>"; 
                    }
                } else {
                    echo "<td>&nbsp;</td>"; 
                }
            }
            echo '</tr>';
        }
        echo '</table>';
		echo '</div>';
        echo '<br />';
    }
}

if (!$att_shown) {
 echo '<p>No attendance data exists.</p>';
}

echo '</div>';
/*
$performance = $attpunc->stop_timer();
echo $performance;
*/
/*RPM
$OUTPUT->footer($course);
*/

?>
