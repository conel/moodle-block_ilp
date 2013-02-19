<?php
/**
 * Databse class to access CONEL's external MIS database for the
 * Learner Progress Review (LPR) Block module.
 *
 * @copyright &copy; 2009 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package LPR
 * @version 1.0
 */
class block_lpr_conel_mis_db {

    protected $db;

    /**
     * Make connection to the MIS database.
     *
     */
    public function __construct() {
        global $CFG;
        // include the necessary DB library
        require_once ($CFG->dirroot.'/lib/adodb/adodb.inc.php');
        // set up the connection
        $this->db = NewADOConnection('oci8');
        $this->db->debug=false;
        $this->db->Connect('ebs.conel.ac.uk', 'ebsmoodle', '82814710', 'fs1');
        $this->db->SetFetchMode(ADODB_FETCH_ASSOC);
    }

    /**
     * Public function to determine which academic year we are currently in.
     *
     */
    public function resolve_year() {
        $academicYearStart = strftime("%Y",strtotime("-8 months",time()));
		$academicYearEnd = strftime("%Y",strtotime("+4 months",time()));
		return "Academic Year $academicYearStart/$academicYearEnd";
    }

    /**
     * Private member function to execute queries and build a moodle style
     * array of objects.
     *
     * @param string $sql The sql string you wish to be executed.
     * @return array The array of objects returned from the query.
     */
    // nkowald - 2011-09-15 - Be careful when using this function
    public function execute_query($sql) {
	
        // execute the query
        $result = $this->db->Execute($sql);
        // initialise the data array
        $data = array();
        // convert the resultset into a Moodle style object
        if(!empty($result)) {
            while (!$result->EOF) {
                $obj = new stdClass;
                foreach (array_keys($result->fields) as $key) {
                    $obj->{$key} = $result->fields[$key];
                }
				$index = reset($result->fields);
                $data[$index] = $obj;
                $result->MoveNext();
            }
        }

        // return an array of objects
        return $data;
		
    }

    /**
     * Gets the average of the learner's attendance over the current year.
     *
     * @param string $learner_id The external id of the learner.
     * @return stdClass The result object.
     */
    public function get_attendance_avg($learner_id) {
		// get the current academic year
        $year = $this->resolve_year();
		$qry = "SELECT (SUM(MARKS_PRESENT)/SUM(MARKS_TOTAL)) AS ATTENDANCE
             FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY
             WHERE STUDENT_ID = '{$learner_id}'
               AND ACADEMIC_YEAR = '{$year}'
               AND MARKS_TOTAL > 0";
			   
		//if ($USER->id = 16772) var_dump($qry);
		
        return array_pop($this->execute_query(
			"SELECT (SUM(MARKS_PRESENT)/SUM(MARKS_TOTAL)) AS ATTENDANCE
             FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY
             WHERE STUDENT_ID = '{$learner_id}'
               AND ACADEMIC_YEAR = '{$year}'
               AND MARKS_TOTAL > 0"
        ));
    }

    /**
     * Gets the average of the learner's punctuality over the current year.
     *
     * @param string $learner_id The external id of the learner.
     * @return stdClass The result object.
     */
    public function get_punctuality_avg($learner_id) {	
        // get the current academic year
        $year = $this->resolve_year();
			   
        return array_pop($this->execute_query(
			"SELECT (SUM(PUNCT_POSITIVE)/SUM(MARKS_PRESENT)) AS PUNCTUALITY
             FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY
             WHERE STUDENT_ID = '{$learner_id}'
               AND ACADEMIC_YEAR = '{$year}'
               AND MARKS_PRESENT > 0"
        ));
    }

    /**
     * Gets the average of the learner's attendance over the current year, for
     * each qualification.
     *
     * @param string $learner_id The external id of the learner.
     * @return stdClass The result object.
     */
    public function get_attendance_qual_avg($learner_id) {
        // get the current academic year
        $year = $this->resolve_year();
        return $this->execute_query(
            "SELECT COURSE_TITLE, COURSE_CODE,
                    (SUM(MARKS_PRESENT)/SUM(MARKS_TOTAL)) AS ATTENDANCE,
                    (SUM(PUNCT_POSITIVE)/SUM(MARKS_PRESENT)) AS PUNCTUALITY
             FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY
             WHERE STUDENT_ID = '{$learner_id}'
               AND ACADEMIC_YEAR = '{$year}'
               AND MARKS_TOTAL > 0
            GROUP BY COURSE_TITLE, COURSE_CODE"
        );
    }
	
		/**
		 * Gets the attendance of a learner by modules
		 *
		 * @param string $learner_id The external id of the learner.
		 * @return stdClass The result object.
		 */
		public function get_attendance_by_modules($learner_id, $modules) {

			return array_pop($this->execute_query(
				"SELECT (SUM(MARKS_PRESENT)/SUM(MARKS_TOTAL)) AS ATTENDANCE
				 FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY
				 WHERE STUDENT_ID = '{$learner_id}'
				   AND MODULE_CODE IN  ('".implode("','", $modules)."')
				   AND MARKS_TOTAL > 0"
			));
		
		}

		/**
		 * Gets the punctuality of a learner by modules
		 *
		 * @param string $learner_id The external id of the learner.
		 * @return stdClass The result object.
		 */
		public function get_punctuality_by_modules($learner_id, $modules) {

			return array_pop($this->execute_query(
				"SELECT (SUM(PUNCT_POSITIVE)/SUM(MARKS_PRESENT)) AS PUNCTUALITY
				 FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY
				 WHERE STUDENT_ID = '{$learner_id}'
				   AND MODULE_CODE IN  ('".implode("','", $modules)."')
				   AND MARKS_PRESENT > 0"
			));				
				
		}	
							
		
		/**
		 * Gets the a list of the modules a learner is enrolled in
		 *
		 * @param string $learner_id The external id of the learner.
		 * @return stdClass The result object.
		 */
		public function list_modules($learner_id) {
			// get the current academic year
			$year = $this->resolve_year();
			
			$query = "SELECT DISTINCT MODULE_CODE, MODULE_DESC, PUNCT_POSITIVE, MARKS_PRESENT, MARKS_TOTAL 
				FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY
				WHERE STUDENT_ID = '{$learner_id}'
				AND ACADEMIC_YEAR = '{$year}'";
			
			return $this->execute_query($query);				
		}
		
		/**
		 * nkowald - 2011-03-17 - Added fix in a new method in case list_modules being used elsewhere.
		 * Distinct removes rows containing same values, we need this for correct attendance and punctuality data
		 *
		 * Gets the a list of the module code and module description a learner is enrolled in
		 *
		 * @param string $learner_id The external id of the learner.
		 * @return stdClass The result object.
		 */
		public function list_distinct_modules($learner_id) {
			// get the current academic year
			$year = $this->resolve_year();
			
			// Changed to JUST get distinct module codes and module description - SUMMED data can be retrieved based on module code
			$query = "SELECT DISTINCT MODULE_CODE, MODULE_DESC  
				 FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY
				 WHERE STUDENT_ID = '{$learner_id}'
				 AND ACADEMIC_YEAR = '{$year}'";
			
			return $this->execute_query($query);				
		}
}
?>