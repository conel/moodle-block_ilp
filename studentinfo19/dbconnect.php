<?php

require_once($CFG->dirroot.'/lib/adodb/adodb.inc.php');

$mis = NewADOConnection('oci8');

$mis->SetFetchMode(ADODB_FETCH_ASSOC);
$mis->debug = false;
$mis->NLS_DATE_FORMAT ='DD-MON-YYYY';

if ($mis->Connect('ebs.conel.ac.uk', 'ebsmoodle', '82814710', 'fs1')){

	$academicYearStart = strftime("%Y",strtotime("-8 months",time()));
	$academicYearEnd = strftime("%Y",strtotime("+4 months",time()));

	$academicYear = $academicYearStart.'/'.$academicYearEnd;
	$academicYear2 = $academicYearStart.$academicYearEnd;
	$academicYear3 = 'Academic Year '.$academicYearStart.'/'.$academicYearEnd;

}

?>