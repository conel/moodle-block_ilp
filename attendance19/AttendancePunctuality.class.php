<?php

    /**
     * AttendancePunctuality Class
     *
     * @author Nathan Kowald <NKowald@conel.ac.uk>
     * @version 1.0
     * @description Talks to EBS and gets attendance and punctuality info for Moodle
     *
     */

    // Make sure config is included so we can use MDL database functions
    require_once($_SERVER['DOCUMENT_ROOT'] . '\config.php');
    // Use the execute_query method ULCC created
    require_once('block_lpr_conel_mis_db.php');

    class AttendancePunctuality extends block_lpr_conel_mis_db {

        public $valid_terms;
        public $errors;
        public $timer_start;
        public $marks_key;
        public $academic_year;
        public $academic_year_4digit;
        public $debug; // show errors?
        public $current_term_no; // 1 
        
        function __construct() {

            // call parent constructor to set up db
            parent::__construct();

            $this->debug = false; // true on dev or when adding features
            $this->valid_terms = array(1, 2, 3);
            $this->errors = array();
            $this->timer_start = '';
            // Start the timer as soon as the class is instantiated
            $this->start_timer();

            $this->academic_year = $this->resolve_year();
	        $this->academic_year_4digit = $this->getAcYear4Digit();
			
	        $this->current_term_no = $this->getCurrentTermNo();

            $this->marks_key['/'] = 'Present';
            $this->marks_key['O'] = 'Absent';
            $this->marks_key['C'] = 'Class Cancel';
            $this->marks_key['E'] = 'Left Early';
            $this->marks_key['F'] = '5 Minutes Late';
            $this->marks_key['G'] = '10 Minutes Late';
            $this->marks_key['K'] = '20 Minutes Late';
            $this->marks_key['L'] = 'Late';
            $this->marks_key['T'] = 'Tutorial';
            $this->marks_key['V'] = 'Visit/Work Experience';
            $this->marks_key['Z'] = 'Author Late';
            $this->marks_key['A'] = 'Author Absent';
            $this->marks_key['H'] = 'Holiday';
            $this->marks_key['S'] = 'Sick';
			
	    $query = "ALTER SESSION SET NLS_DATE_FORMAT='DD/MM/YYYY'";
	    $this->db->Execute($query);
			
        }

        /**
         * start_timer
         *
         * Used for measuring script performance, starts a timer
         */
        public function start_timer() {
            // Performance monitoring
            $time = microtime();
            $time = explode(' ', $time);
            $time = $time[1] + $time[0];
            $this->timer_start = $time;
        }

        /**
         * stop_timer
         *
         * Used for measuring script performance, stops the timer: returns data
         */
        public function stop_timer() {
            $time = microtime();
            $time = explode(' ', $time);
            $time = $time[1] + $time[0];
            $finish = $time;
            $total_time = round(($finish - $this->timer_start), 4);
            return 'Page generated in '.$total_time.' seconds.'."\n";
        }
		
        public function getAcYear4Digit() {
            // Get four digit academic year code - 
            $now = time();
            $query = "SELECT ac_year_code FROM mdl_terms WHERE term_start_date < $now and term_end_date > $now";
            global $DB;
			if ($ac_years = $DB->get_records_sql($query)) {
                foreach($ac_years as $year) {
                   $ac_year = $year->ac_year_code; 
                }
                return $ac_year;
            } else {
                // This covers inbetween term periods
                return $this->resolve_year_4digit();
            }
        }
         
        public function resolve_year_4digit() {
            $academicYearStart = strftime("%y",strtotime("-8 months",time()));
		    $academicYearEnd = strftime("%y",strtotime("+4 months",time()));
            $year = $academicYearStart . $academicYearEnd;
	        return $year;
        }

        /**
         * getTermDates
         *
         * @return - Returns indexed array containing term start and end dates or false if none found
         */
        public function getCurrentTermDates() {

            // Now we've got current ac_year code, look up term start/end dates
            $query = "SELECT term_code, term_start_date, term_end_date FROM mdl_terms WHERE ac_year_code = ".$this->academic_year_4digit."";
			global $DB;
            if ($terms = $DB->get_records_sql($query)) {
                $current_terms = array();
                foreach($terms as $term) {
                   $current_terms[$term->term_code] = array('start' => $term->term_start_date, 'end' => $term->term_end_date); 
                }
                return $current_terms;
            } else {
				$error = "Could not get term dates for this academic year: ".$this->academic_year_4digit;
				if (!in_array($error, $this->errors)) {
					$this->errors[] = $error;
				}
                return false;
            }
        }
		
		/**
		*
		* Gets the current term from the mdl_terms table based on current unixtimestamp
		*/
		public function getCurrentTermNo() {
			$timestamp = time();
			// This query makes sure that even if today's timestamp is after term 1 end date but before term 2 start date, it'll still get the correct term (term 1)
			$query = sprintf("SELECT term_code FROM mdl_terms WHERE term_start_date < %d and ac_year_code = '%4d' ORDER BY term_code desc LIMIT 1", 
				$timestamp,
				$this->academic_year_4digit
			);
			global $DB;
			if ($term_no = $DB->get_records_sql($query)) {
				foreach ($term_no as $term) {
					$term_code = $term->term_code;
				}
				if (in_array($term_code, $this->valid_terms)) {
					return $term_code;
				} else {
					$this->errors[] = "Invalid term number: valid terms are 1, 2, 3";
					return false;
				}
			} else {
				$this->errors[] = "Current term not found. Term dates for the current academic year (".$this->academic_year_4digit.") needs to be entered into the 'mdl_terms' table";
				return false;
			}
		}

		/** 
		* sortByDate
		* This sorts the attendace and punctuality module data by weekday, then start time
		*/
		public function sortByDate($a, $b) {
			if ($a['day_num'] < $b['day_num']) return -1;
			if ($a['day_num'] > $b['day_num']) return 1;
			if ($a['start_time'] < $b['start_time']) return -1;
			if ($a['start_time'] > $b['start_time']) return 1;
			return 0;
		}
		
        /**
         * getDistinctModuleSlots
         *
         * @param     int    $student_id   external learner id
         * @param     int    $term         the term to get data from
         * @return    array  Returns distinct module slots with /01, /02 etc added to distinguish slots or false on fail
         */
        public function getDistinctModuleSlots($student_id, $term='') {
            if ($student_id != '' && is_numeric($term) && in_array($term, $this->valid_terms)) {
			
				// nkowald - 2012-01-03 - Make this term based, get term dates
				$start_term = '';
				$end_term = '';
				
				// TO DO - Handle instances of NO term date
				if ($term_dates = $this->getCurrentTermDates()) {
					$start_term = $term_dates[$term]['start'];
					// convert to dd/mm/yyyy format
					$start_term = date('d/m/Y', $start_term);
					$end_term = $term_dates[$term]['end'];
					$end_term = date('d/m/Y', $end_term);
				}
				
				/* Possibly only show modules with registers
					AND REG_MARK IS NOT NULL 
				*/
				
                $query = sprintf("SELECT 
                    DISTINCT REGISTER_ID, MODULE_CODE, MODULE_DESCRIPTION, REG_DAY, REG_DAY_NUM, START_TIME, END_TIME 
                    FROM FES.MOODLE_LEARNER_REGISTER 
                    WHERE STUDENT_ID = %d 
					AND TO_DATE(REG_DATE, 'DD/MM/YYYY') >= TO_DATE('%s', 'DD/MM/YYYY') 
                    AND TO_DATE(REG_DATE, 'DD/MM/YYYY') <= TO_DATE('%s', 'DD/MM/YYYY') 
                    ORDER BY REGISTER_ID, REG_DAY_NUM, START_TIME", 
					$student_id,
					$start_term,
					$end_term
				);
                
                if ($slots = $this->db->Execute($query)) {
                    $data = array();

                     if(!empty($slots)) {
                        while (!$slots->EOF) {
							// Create slot name (MODULE_CODE + '/01' format)
							$slot_num = 1;
							$slot_code = $slots->fields['MODULE_CODE'] . '/' . sprintf("%02d", $slot_num);
							while (array_key_exists($slot_code, $data)) {
								++$slot_num;
								$slot_code = $slots->fields['MODULE_CODE'] . '/' . sprintf("%02d", $slot_num);
							}
							$data[$slot_code]['register_id'] = trim($slots->fields['REGISTER_ID']);
							$data[$slot_code]['module_code'] = trim($slots->fields['MODULE_CODE']);
							$data[$slot_code]['module_desc'] = trim($slots->fields['MODULE_DESCRIPTION']);
							$data[$slot_code]['day'] = trim($slots->fields['REG_DAY']);
							$data[$slot_code]['day_num'] = trim($slots->fields['REG_DAY_NUM']);
							$data[$slot_code]['start_time'] = trim($slots->fields['START_TIME']);
							$data[$slot_code]['end_time'] = trim($slots->fields['END_TIME']);
							$slots->MoveNext();

                        }
                     }

                    if (count($data) > 0) {
						// Order the associative array by Day, Start
						uasort($data, array($this, 'sortByDate'));
                        return $data;
                    } else {
                        $this->errors[] = 'No timetable slots exist for this student';
                        return false;
                    }
                } else {
                    $this->errors[] = 'No timetable slots found for this user';
                }
            } else {
                $this->errors[] = 'Invalid student ID or term';
                return false;
            }
        }

        /**
         * handleBlanks
         *
         * Easy method to handle values, if blank leave blank, else return the value
         */
        private function handleBlanks($value='') {
            if ($value == '') {
                return "";
            } else {
                return $value;
            }
        }

        /**
         * getAttendancePunctuality
         *
         * @param     int    $student_id   external learner id
         * @param     int    $term         the term to get data from
         * @return    array  Returns array containing aggregate ATT & PUNCT data for each distinct slot
         */
        public function getAttendancePunctuality($student_id='', $term='') {
            if ($student_id != '' && is_numeric($term) && in_array($term, $this->valid_terms)) {
                // First build array of distinct timetable slots (per module, day, start, end)
                $slots = $this->getDistinctModuleSlots($student_id, $term);

                if (count($slots) > 0) {
                    // Get the rest of the details based on module, day, start, end
                    foreach ($slots as $key => $value) {

						/* old way of getting data from term 1 only
						AND ((TO_DATE(REGISTER_DATE, 'DD/MM/YYYY') - TO_DATE('01/01/1970','DD/MM/YYYY')) * (86400)) > %d 
						AND ((TO_DATE(REGISTER_DATE, 'DD/MM/YYYY') - TO_DATE('01/01/1970','DD/MM/YYYY')) * (86400) < %d) 
						*/
						/*
                        $query = sprintf("SELECT 
                            SUM(POS_ATT) AS SESSIONS_PRESENT, 
                            (SUM(TOTAL) - SUM(POS_ATT)) AS SESSIONS_ABSENT, 
                            (SUM(POS_ATT) / SUM(TOTAL)) AS ATTENDANCE, 
                            (SUM(POS_PUNCT) / SUM(POS_ATT)) AS PUNCTUALITY, 
                            SUM(POS_PUNCT) AS SESSIONS_ON_TIME, 
                            (SUM(POS_ATT) - SUM(POS_PUNCT)) AS SESSIONS_LATE 
                                FROM FES.MOODLE_ATT_PUNCT_T 
                                WHERE STUDENT_ID = %d 
                                AND REGISTER_ID = %d 
								AND ACADEMIC_YEAR = '%s'",
                                $student_id,
                                $value['register_id'],
                                $this->academic_year
                            );
						*/
						
						// nkowald - 2012-01-03 - Make this term based, get term dates
						$start_term = '';
						$end_term = '';
						
						// TO DO - Handle instances of NO term date
						if ($term_dates = $this->getCurrentTermDates()) {
							$start_term = $term_dates[$term]['start'];
							// convert to dd/mm/yyyy format
							$start_term = date('d/m/Y', $start_term);
							$end_term = $term_dates[$term]['end'];
							$end_term = date('d/m/Y', $end_term);
						}
						
						//removing formatting of register date
						
						$query = sprintf("SELECT 
							SUM(POS_ATT) AS SESSIONS_PRESENT, 
                            (SUM(TOTAL) - SUM(POS_ATT)) AS SESSIONS_ABSENT, 
                            (SUM(POS_ATT) / SUM(TOTAL)) AS ATTENDANCE, 
                            (SUM(POS_PUNCT) / SUM(POS_ATT)) AS PUNCTUALITY, 
                            SUM(POS_PUNCT) AS SESSIONS_ON_TIME, 
                            (SUM(POS_ATT) - SUM(POS_PUNCT)) AS SESSIONS_LATE 
									FROM FES.MOODLE_ATT_PUNCT_T 
									WHERE STUDENT_ID = %d 
									AND REGISTER_ID = %d 
									AND TO_DATE(REGISTER_DATE, 'DD/MM/YYYY') >= TO_DATE('%s', 'DD/MM/YYYY') 
									AND TO_DATE(REGISTER_DATE, 'DD/MM/YYYY') <= TO_DATE('%s', 'DD/MM/YYYY') 
									AND ACADEMIC_YEAR = '%s'",
									$student_id,
									$value['register_id'],
									$start_term,
									$end_term,
									$this->academic_year
						);
                        if ($att_punc = $this->execute_query($query)) {
                            $data = array();
                            foreach ($att_punc as $attpun) {
                                $slots[$key]['attendance'] = $this->handleBlanks($attpun->ATTENDANCE);
                                $slots[$key]['punctuality'] = $this->handleBlanks($attpun->PUNCTUALITY);
                                $slots[$key]['sessions_present'] = $this->handleBlanks($attpun->SESSIONS_PRESENT);
                                $slots[$key]['sessions_absent'] = $this->handleBlanks($attpun->SESSIONS_ABSENT);
                                $slots[$key]['sessions_on_time'] = $this->handleBlanks($attpun->SESSIONS_ON_TIME);
                                $slots[$key]['sessions_late'] = $this->handleBlanks($attpun->SESSIONS_LATE);
                            }
                        }
                    }

                    // Return the data, including the extra info
					
                    return $slots;

                } else {
                    $this->errors[] = 'No timetable slots found for this user';
                    return false;
                }
            } else {
                $this->errors[] = 'Invalid student or term id';
                return false;
            }
        }

        /**
         * getRegisterWeeks
         *
         * @param     int    $student_id   external learner id
         * @param     int    $term         the term to get data from
         * @return    array  Returns array containing week dates for the given term 
         *                   Gets term start date then adds 7 days to get the next week date
         */
        public function getRegisterWeeks($student_id='', $term='') {
            if ($terms = $this->getCurrentTermDates()) {

                $register_week_start = $terms[$term]['start'];
                $register_term_end = $terms[$term]['end'];
				
				// nkowald - 2012-01-11 - Date needs to be a Monday
				if ((date('N', $register_week_start)) != 1) {
					while((date('N', $register_week_start)) != 1) {
						$register_week_start -= 86400;
					}
				}

                $register_week_fmt = date('d/m/Y', $register_week_start);
                $reg_weeks = array();
                $reg_weeks[] = $register_week_fmt;

                // While the current register week is in the current term, add a week
                while($register_week_start < $register_term_end) {
                    $register_week_start += 604800; // add a week in seconds
                    $register_week_fmt = date('d/m/Y', $register_week_start); // format as dd/mm/yyyy
                    if ($register_week_start < $register_term_end) {
                        $reg_weeks[] = $register_week_fmt;
                    }
                }
                if (count($reg_weeks) > 0) {
                    return $reg_weeks;
                } else {
                    return false;
                }

            } else {
                $this->errors[] = 'Could not get current term dates';
                return false;
            }
        }
        
        /**
         * getMarkForModuleSlot
         *
         * @param     int     $student_id   external learner id
         * @param     int     $term         the term to get data from
         * @param     string  $register_id  the register_id to get mark from
         * @param     string  $date         the date to get mark from
         *
         * @return    boolean               If mark is found returns it else returns false
         */
        public function getMarkForModuleSlot($student_id='',$term='',$register_id='',$date='') {
            $query = sprintf("SELECT REG_MARK 
                FROM FES.MOODLE_LEARNER_REGISTER 
                WHERE STUDENT_ID = %d 
                AND REGISTER_ID = '%d' 
                AND REG_DATE = '%s' 
                AND REG_MARK IS NOT NULL", 
                $student_id, 
                $register_id,
                $date
            );
	
            if ($registers = $this->execute_query($query)) {
                $register_mark = '';
                foreach ($registers as $reg) {
                    $register_mark = $reg->REG_MARK;
                }
                if ($register_mark != '') {
                    return $register_mark;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

         /**
         * Gets the average of the learner's attendance over the current year.
         *
         * @param string $learner_id The external id of the learner.
         * @return stdClass The result object.
         */
        public function get_attendance_avg($learner_id) {

            $query = sprintf("SELECT (SUM(POS_ATT) / SUM(TOTAL)) AS ATTENDANCE 
                FROM FES.MOODLE_ATT_PUNCT_T 
                WHERE STUDENT_ID = '%d' 
                AND ACADEMIC_YEAR = '%s'",
                $learner_id, 
                $this->academic_year
            );
			
            return array_pop($this->execute_query($query));
        }

        /**
         * Gets the average of the learner's punctuality over the current year.
         *
         * @param string $learner_id The external id of the learner.
         * @return stdClass The result object.
         */
        public function get_punctuality_avg($learner_id) {	
                   
            $query = sprintf("SELECT (SUM(POS_PUNCT) / SUM(POS_ATT)) AS PUNCTUALITY 
                FROM FES.MOODLE_ATT_PUNCT_T 
                WHERE STUDENT_ID = '%d' 
                AND ACADEMIC_YEAR = '%s'",
                $learner_id, 
                $this->academic_year
            );

            return array_pop($this->execute_query($query));
        }


        /**
         * getAttPuncData
         *
         * @param int $figure the 0.34 etc. value of passed attendance or punctuality
         * @return array array of att or punc values for display
         */
        public function getAttPuncData($figure='') {
			$figure = ($figure != '') ? round($figure * 100, 2) : '0';
			// Here comes the calculations
			$colour = '';
			if ($figure >= 92) {
				$colour = 'green';
			} else if ($figure >= 87 && $figure < 92) {
				$colour = 'amber';
			} else if ($figure < 87 && is_numeric($figure)) {
				$colour = 'red';
			}
			// Now return an array of attenace data to use
			$data = array();
			$data['decimal'] = $figure;
			$data['colour'] = $colour;
			$data['formatted'] = (is_numeric($figure)) ? $figure . '%' : '';
			return $data;
        }
		
		// $module_code = 'NV1MHAR1-1DA11A/FSM' (for example)
		public function getAttPuncForModule($learner_id, $module_code) {

			$query = sprintf("SELECT 
					(SUM(POS_ATT) / SUM(TOTAL)) AS ATTENDANCE,
					(SUM(POS_PUNCT) / SUM(POS_ATT)) AS PUNCTUALITY 
					FROM FES.MOODLE_ATT_PUNCT_T 
					WHERE STUDENT_ID = '%d'
					AND MODULE_CODE = '%s'
					AND ACADEMIC_YEAR = '%s'",
					$learner_id,
					$module_code,
					$this->academic_year
			);
			
			/*
			$query = sprintf("SELECT 
					(CASE WHEN (SUM(TOTAL)) = 0 THEN 0 ELSE (SUM(POS_ATT) / SUM(TOTAL)) END) AS ATTENDANCE, 
					(CASE WHEN (SUM(POS_ATT)) = 0 THEN 0 ELSE (SUM(POS_PUNCT) / SUM(POS_ATT)) END) AS PUNCTUALITY 
					FROM FES.MOODLE_ATT_PUNCT_T 
					WHERE STUDENT_ID = '%d'
					AND MODULE_CODE = '%s'
					AND ACADEMIC_YEAR = '%s'",
					$learner_id,
					$module_code,
					$this->academic_year
			);
			*/
			
			if ($attpunc = $this->execute_query($query)) {
				 foreach ($attpunc as $attpun) {
					$attendance = $this->handleBlanks($attpun->ATTENDANCE);
					$punctuality = $this->handleBlanks($attpun->PUNCTUALITY);
				}
				$attendance = round($attendance * 100, 2);
				$punctuality = round($punctuality * 100, 2);
				
				$data = array('attendance' => $attendance, 'punctuality' => $punctuality);
				return $data;
				
			} else {
				$this->errors[] = 'No attendance or punctuality data for learner: '.$learner_id.' and module: '.$module_code.'';
				return false;
			}
			
		}
		
		// nkowald - 2011-10-13 - Adding list modules function here (called from inside targets)
		public function getModules($ebs_learner_id) {

            /*
            // nkowald - 2012-03-08 - Changing once again. This view should contain ONLY active enrolments for the student
            $query = sprintf("SELECT DISTINCT MODULE_CODE, MODULE_DESC 
                                FROM FES.MOODLE_CURRENT_ENROLMENTS 
                                WHERE ENROL_STATUS = 'Active' 
                                AND PERSON_STATUS = 'student' 
                                AND id = %d  
                                AND ACADEMIC_YEAR = '%s'",
                    $ebs_learner_id,
                    $this->academic_year
                    );
            */    
            /*
			$query = sprintf("SELECT DISTINCT MODULE_CODE, MODULE_DESC FROM FES.MOODLE_ATT_PUNCT_T WHERE STUDENT_ID = %d AND ACADEMIC_YEAR = '%s' ORDER BY MODULE_CODE ASC", 
				$ebs_learner_id,
				$this->academic_year
			);
            */
            
            // nkowald - 2012-03-09 - Change ONCE AGAIN! This view should only contain CURRENT enrolments. Using this table because it holds SLOT_DATE.
            //                        We can use the last SLOT_DATE to work out if the module has ended or not. If it has: remove it from the modules array.
            
            $query = sprintf("SELECT DISTINCT MODULE_CODE, EVENT_DESC, SLOT_DATE from FES.MOODLE_STUDENT_TIMETABLE where PERSON_CODE = %d ORDER BY MODULE_CODE, SLOT_DATE ASC",
                $ebs_learner_id
            );
			
			if ($modules = $this->execute_query($query)) {
				$learner_modules = array();
				foreach ($modules as $module) {
					$learner_modules[$module->MODULE_CODE]['module_code'] = $module->MODULE_CODE;
					$learner_modules[$module->MODULE_CODE]['module_desc'] = $module->EVENT_DESC;
					$learner_modules[$module->MODULE_CODE]['slot_date']   = $module->SLOT_DATE;
				}
                
                // nkowald - 2012-03-09 - If the max slot date is in the past: don't show it.
                foreach ($learner_modules as $key => $value) {
                    $today = time();
                    // Last slot date for module
                    $slot_date = $learner_modules[$key]['slot_date'];
                    
                    // strtotime requires US format 2012/03/30 so we have to convert our slot date to yyyy/mm/dd
                    $slots = explode('/', $slot_date);
                    $slot_day = $slots[0];
                    $slot_month = $slots[1];
                    $slot_year = $slots[2];
                    
                    $slot_formatted = $slot_year . "/" . $slot_month . "/" . $slot_day;
                    $max_slot_ts = strtotime($slot_formatted);
                    
                    if ($max_slot_ts < $today) {
                        unset($learner_modules[$key]);
                    }
                }
                
				// Now we have distinct module data, get and add attendance and punctuality to these figures
				/* No longer needed
				foreach($learner_modules as $key => $value) {
					// Get attendance and punctuality data for the given module
					$apdata = $this->getAttPuncForModule($ebs_learner_id, $value['module_code']);
					if (is_array($apdata)) {
						$learner_modules[$key]['attendance'] = $apdata['attendance'];
						$learner_modules[$key]['punctuality'] = $apdata['punctuality'];
					} else {
						$this->errors[] = "No attendance and punctuality data for module ".$value['module_code'];
					}
				}
				*/
				return $learner_modules;
			} else {
				$this->errors[] = 'No modules found for learner: '.$ebs_learner_id;
				return false;
			}
		}
		
		// nkowald - 2011-11-17 - Get Module Description for Module Code
		public function getModuleDesc($module_code) {
			$query = sprintf("SELECT DISTINCT MODULE_DESC FROM FES.MOODLE_ATT_PUNCT_TEST WHERE MODULE_CODE = '%s' AND ACADEMIC_YEAR = '%s'",
				$module_code,
				$this->academic_year
			);
			$description = "";
			if ($module_desc = $this->execute_query($query)) {
				foreach ($module_desc as $desc) {
					$description = $desc->MODULE_DESC;
				}
				return $description;
			} else {
				// We need this to fail gracefully
				//$this->errors[] = "No module description found for this module ".$module_code;
				return $description;
			}
		}

		// nkowald - 2011-10-18 - Get Modules completion data and tutor information
		// $user must be a moodle user object as both id and idnumber are needed
        public function getModuleCompletion(stdClass $user, $term=1) {
		
			// EBS has more reliable data so lets get our data from EBS
			$modules = $this->getModules($user->idnumber);
			
			if ($modules) {
			
				// Put modules codes into an array
				$module_codes = array();
				foreach ($modules as $key => $value) {
					$module_codes[] = "'".$modules[$key]['module_code']."'";
				}
				// Put modules codes into a CSV
				$csv_modules = implode(',', $module_codes);
			
				// We only need to query the mdl_module_complete table returning the number of modules for the user and completion status 3/6 for example.
				$query = sprintf("SELECT SUM(complete) AS complete_modules, COUNT(module_code) AS num_modules FROM mdl_module_complete WHERE mdl_student_id = %d and academic_year = %4d and term = %1d and module_code IN (%s) ORDER BY module_code ASC", 
					$user->id,
					$this->academic_year_4digit,
					$term,
					$csv_modules
				);
				
				if ($comp = get_record_sql($query)) {
					$num_modules = ($comp->num_modules != '') ? $comp->num_modules : 0;
					$complete_modules = ($comp->complete_modules != '') ? $comp->complete_modules : 0;

					$completion_html = $complete_modules . "/" . $num_modules;
					return $completion_html;
				} else {
					return false;
					$this->errors[] = 'Module details not found for this learner ' . $learner_id;
				}
			
			} else {
				return false;
				$this->errors[] = 'No modules found for this learner ' . $learner_id;
			}
        }

        // nkowald - 2011-10-18 - Get Modules completion data and tutor information
        public function getModuleDetails($ebs_learner_id, $term=1) {
            if ($modules = $this->getModules($ebs_learner_id)) {
                foreach ($modules as $key => $value) {
                   // Get tutor and completion for this module 
                    $query = sprintf("SELECT tutor_name, complete, mdl_tutor_id FROM mdl_module_complete WHERE academic_year = '%4d' AND term = %d AND module_code = '%s' and ebs_student_id = '%d'",
                        $this->academic_year_4digit,
                        $term,
                        strtoupper($value['module_code']),
                        $ebs_learner_id
                    );
                    $tutor = '';
                    $complete = 0;
                    if ($module_statuses = get_records_sql($query)) {
                        foreach ($module_statuses as $status) {
                            $tutor = $status->tutor_name;
                            $tutor_id = $status->mdl_tutor_id;
                            $complete = $status->complete;
                        }
                    }
                    // Add these details to the modules array
                   $modules[$key]['tutor'] = $tutor;
                   $modules[$key]['complete'] = $complete; 
                   $modules[$key]['tutor_id'] = $tutor_id;
                }
                return $modules;

            } else {
                $this->errors[] = 'No modules found for this learner '. $ebs_learner_id;
                return false;
            }
        }


        /**
         * __call
         *
         * @return if a method is called that doesn't exist, throw exception with this message
         */
        public function __call($name, $arg) {
            throw new BadMethodCallException("Sorry, this method '$name' does not exist");
        }


        function __destruct() {
            if ($this->debug === true) {
                if (count($this->errors) > 0) {
                    echo '<div class="errors">';
                    echo '<h3>Errors:</h3>';
                    echo '<ul>';
                    foreach ($this->errors as $error) {
                        echo "<li>$error</li>";
                    }
                    echo '</ul>';
                    echo '</div>';
                }
            }
        }

    } // AttendancePunctuality

?>
