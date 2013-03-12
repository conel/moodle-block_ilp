<?PHP 

//  Lists the student info texts relevant to the student.
//  with links to edit for those who can. 

    require_once('../../../config.php');
    require_once('block_ilp_student_info_lib.php');
    require_once('block_ilp_lib.php');
    require_once('access_context.php');
	// For connecting to MIS
	require_once('dbconnect.php');
	require_once('block_lpr_conel_mis_db.php'); // include the connection code for CONEL's MIS db
	
	$PAGE->requires->css('/blocks/ilp/styles.css', true);
	
	//require_login();
	
	include_once($CFG->dirroot. '/blocks/bksb/BksbReporting.class.php');
    $bksb = new BksbReporting();

    $contextid    	= optional_param('contextid', 0, PARAM_INT);               // one of this or
    $courseid     	= optional_param('courseid', SITEID, PARAM_INT);                  // this are required
    $userid       	= optional_param('id', 0, PARAM_INT);                  // this is required
    $view       	= optional_param('view', 'all', PARAM_TEXT); 
    $text       	= optional_param('text', 'all', PARAM_TEXT);   
	$module 		= 'project/ilp';
	$config 		= get_config($module);

    if (!$userid) {
        $userid = $USER->id ;
    }

    include('access_context.php'); 

	global $DB;
	
    $user = $DB->get_record('user',array('id'=>$userid));

/// Print headers
    if ($course->id != SITEID) {
        print_header(fullname($user)." Student Info", $course->fullname,"<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> "."<a href=\"../../blocks/ilp/view.php?courseid=$course->id&amp;id=$user->id\">".get_string("ilp", "block_ilp")."</a> -> ".get_string('ilp_student_info','block_ilp_student_info')." -> ".fullname($user)."", "", "", true, "&nbsp;", navmenu($course));
    } else {
        print_header(fullname($user)." Student Info", $course->fullname,"<a href=\"../../blocks/ilp/view.php?courseid=$course->id&amp;id=$user->id\">".get_string("ilp", "block_ilp")."</a> -> ".get_string('ilp_student_info','block_ilp_student_info')." -> ".fullname($user)."", "", "", true, "", navmenu($course));
    }
	
	//<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userid.'&course='.$courseid.'">
	
	// Get Entry Qualification data for use in template
	$query = "SELECT AWARD_TITLE, ACHIEVED_YEAR, GRADE, AWARDING_BODY_CODE, AWARDING_BODY, QUAL_TYPE, QUAL_DESC FROM FES.MOODLE_ATTAINMENTS WHERE STUDENT_ID = '".$user->idnumber."'";
	
	$user_quals = array();
	if ($quals = $mis->Execute($query)) {
		
		$i = 0;
		
		while (!$quals->EOF) {
		
			$user_quals[$i]['award_title'] = $quals->fields["AWARD_TITLE"];
			$user_quals[$i]['achieved_year'] = $quals->fields["ACHIEVED_YEAR"];
			$user_quals[$i]['grade'] = $quals->fields["GRADE"];
			$user_quals[$i]['awarding_body'] = ($quals->fields["AWARDING_BODY"] != '') ? $quals->fields["AWARDING_BODY"] : '&ndash;' ;
			$user_quals[$i]['qual_type'] = $quals->fields["QUAL_TYPE"];
			$user_quals[$i]['qual_desc'] = $quals->fields["QUAL_DESC"];
			
			$quals->moveNext();
			$i++;
		}
	} else {
		echo '<pre>Connection to EBS failed</pre>';
	}

if (file_exists('templates/custom/template.php')) {
  include('templates/custom/template.php');
}elseif (file_exists('template.php')) {
  include('template.php');
}else{
  error("missing template \"$template\"") ; 
}
 
$OUTPUT->footer($course);

?>