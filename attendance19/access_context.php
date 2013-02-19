<?php     
    $userid     = optional_param('id', 0, PARAM_INT); // this is required
    $courseid   = optional_param('courseid', SITEID, PARAM_INT); // this are required

	global $DB;
	
    if (!$userid) {
        $userid = $USER->id ;
    }

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $usercontext = get_context_instance(CONTEXT_USER, $userid);
	
    if (!$usercontext) {
       error("User ID is incorrect");
    }

    if ($courseid) {
	//$DB->get_record($table, array $conditions, $fields='*', $strictness=IGNORE_MISSING)
        if (! $course = $DB->get_record('course', array('id'=>$courseid))) {
            echo("DB");
			//print_object($DB);
			print_error("Course ID is incorrect".$courseid);
        }
        if (! $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id)) {
            error("Context ID is incorrect");
        }
		$context = $coursecontext;
    }

	
	/*RPM
    if (has_capability('moodle/legacy:guest', $sitecontext, NULL, false)) {
        error("You are logged in as Guest.");
    }
	*/
	
    // ACCESS CONTROL
    $access_isgod = 0 ; 
    $access_isuser = 0 ; 
    $access_isteacher = 0 ; 
    $access_istutor = 0 ;  
    
	/*RPM
	if (has_capability('moodle/site:doanything', $sitecontext)) {  // are we god ?
        $access_isgod = 1 ;
    }
	*/
	
    if ($userid == $USER->id) { // are we the user ourselves ?
        $access_isuser = 1;   
    }
    if(isset($coursecontext)){
      if (has_capability('block/ilp:viewotherilp',$coursecontext)) { // are we the teacher on the course ?
	 $access_isteacher = 1;
      }
    }
    if (has_capability('block/ilp:viewotherilp',$usercontext)) { // are we the personal tutor ? 
	 $access_istutor = 1;
	 $context = $usercontext;
    }
    if (!($access_isgod or $access_isuser or $access_isteacher or $access_istutor)) {
        error("insufficient access");
    }

	if(!isset($context)){
		$context = $sitecontext;
	}

?>