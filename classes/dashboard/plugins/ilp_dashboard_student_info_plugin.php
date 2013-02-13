
<?php
/**
 * A class used to display information on a particular student in the ilp 
 *
 *  *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
//require the ilp_plugin.php class 
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_plugin.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_percentage_bar.class.php');



class ilp_dashboard_student_info_plugin extends ilp_dashboard_plugin {
	
	public		$student_id;	
	
	
	function __construct($student_id = null)	{
		//set the id of the student that will be displayed by this 
		$this->student_id	=	$student_id;
		
		//set the name of the directory that holds any files for this plugin
		$this->directory	=	'studentinfo';
		
		parent::__construct();
		
	}
	
	
	
	/**
	 * Returns the 
	 * @see ilp_dashboard_plugin::display()
	 */
	function display()	{	
		global	$CFG,$OUTPUT,$PAGE,$PARSER,$USER;

		//set any variables needed by the display page	
		
		//get students full name
		$student	=	$this->dbc->get_user_by_id($this->student_id);
		
		
		if (!empty($student))	{ 
			$studentname	=	fullname($student);
			$studentpicture	=	$OUTPUT->user_picture($student,array('size'=>100,'return'=>'true')); 
			
			$tutors	=	$this->dbc->get_student_tutors($this->student_id);
			$tutorslist	=	array();
			if (!empty($tutors)) {
				foreach ($tutors as $t) {
					$tutorslist[]	=	fullname($t);
				}					
			} else {
				$tutorslist		=	"";
			}
			
			//get the students current status
			$studentstatus	=	$this->dbc->get_user_status($this->student_id);
			if (!empty($studentstatus)) {
				$statusitem		=	$this->dbc->get_status_item_by_id($studentstatus->parent_id);
			}   
			
			$userstatuscolor	=	get_config('block_ilp', 'passcolour');
			 
			if (!empty($statusitem))	{
				if ($statusitem->passfail == 1) $userstatuscolor	=	get_config('block_ilp', 'failcolour');
                //that's all very well, but if the ilp is up to date, status hex colour is defined, so actually we should always do this...
                //the above logic only allows 2 colours, so is inadequate to the task
                if( !empty( $statusitem->hexcolour ) ){
                    $userstatuscolor = $statusitem->hexcolour;
                }
                //ah that's better
			} 
			
			//TODO place percentage bar code into a class 
			
			$percentagebars	=	array();
						
			//set the display attendance flag to false
			$displayattendance	= false;
			
			/****
			 * This code is in place as moodle insists on calling the settings functions on normal pages
			 * 
			 */
			//check if the set_context method exists
			if (!isset($PAGE->context) === false) {
				
				$course_id = (is_object($PARSER)) ? $PARSER->optional_param('course_id', SITEID, PARAM_INT)  : SITEID;
				$user_id = (is_object($PARSER)) ? $PARSER->optional_param('user_id', $USER->id, PARAM_INT)  : $USER->id;
				
				if ($course_id != SITEID && !empty($course_id))	{ 
					if (method_exists($PAGE,'set_context')) {
						//check if the siteid has been set if not 
						$PAGE->set_context(get_context_instance(CONTEXT_COURSE,$course_id));
					}	else {
						$PAGE->context = get_context_instance(CONTEXT_COURSE,$course_id);		
					}
				} else {
					if (method_exists($PAGE,'set_context')) {
						//check if the siteid has been set if not 
						$PAGE->set_context(get_context_instance(CONTEXT_USER,$user_id));
					}	else {
						$PAGE->context = get_context_instance(CONTEXT_USER,$user_id);		
					}
				}
			} 
		
			$access_viewotherilp	=	has_capability('block/ilp:viewotherilp', $PAGE->context);

			//can the current user change the users status
			$can_editstatus	=	(!empty($access_viewotherilp) && $USER->id != $student->id) ? true : false;
			
			//include the attendance 
			$misclassfile	=	$CFG->dirroot.'/blocks/ilp/classes/dashboard/mis/ilp_mis_attendance_percentbar_plugin.php';
			
			if (file_exists($misclassfile)) {
				
				include_once $misclassfile;
				
				//create an instance of the MIS class
				$misclass	=	new ilp_mis_attendance_percentbar_plugin();
				
				//set the data for the student in question
				$misclass->set_data($this->student_id);
				
				
				$punch_method1 = array($misclass, 'get_student_punctuality');
				$attend_method1 = array($misclass, 'get_student_attendance');

        
					        //check whether the necessary functions have been defined
		        if (is_callable($punch_method1,true)) {
		        	$misinfo	=	new stdClass();
	    	        

	    	        if ($misclass->get_student_punctuality() != false) {
		    	        //calculate the percentage
		    	        
		    	        $misinfo->percentage	=	$misclass->get_student_punctuality();	
	    	        
	    		        $misinfo->name	=	get_string('punctuality','block_ilp');
	    	        	
	    		        //pass the object to the percentage bars array
	    	    	    $percentagebars[]	=	$misinfo;
	    	        }
	        	}

				//check whether the necessary functions have been defined
		        if (is_callable($attend_method1,true) ) {
		        	$misinfo	=	new stdClass();
	    	        
	    	        //if total_possible is empty then there will be nothing to report
		        	if ($misclass->get_student_attendance() != false) {
	    	        	//calculate the percentage
	    	        	$misinfo->percentage	=	$misclass->get_student_attendance();
	    	        
	    	        	$misinfo->name	=	get_string('attendance','block_ilp');

	    	        	$percentagebars[]	=	$misinfo;
	    	        }
	    	        
	        	}

			}

			
			$misoverviewplugins	=	false;

			if ($this->dbc->get_mis_plugins() !== false) {
				
				$misoverviewplugins	=	array();
				
				//get all plugins that mis plugins
				$plugins = $CFG->dirroot.'/blocks/ilp/classes/dashboard/mis';
				
				$mis_plugins = ilp_records_to_menu($this->dbc->get_mis_plugins(), 'id', 'name');
				
				foreach ($mis_plugins as $plugin_file) {
					
					if (file_exists($plugins.'/'.$plugin_file.".php")) {
					    require_once($plugins.'/'.$plugin_file.".php");
					    
					    // instantiate the object
					    $class = basename($plugin_file, ".php");
					    $pluginobj = new $class();
					    $method = array($pluginobj, 'plugin_type');
						
					    if (is_callable($method,true)) {
					    	//we only want mis plugins that are of type overview 
					        if ($pluginobj->plugin_type() == 'overview') {
					        	
					        	//get the actual overview plugin
					        	$misplug	=	$this->dbc->get_mis_plugin_by_name($plugin_file);
					        	
					        	//if the admin of the moodle has done there job properly then only one overview mis plugin will be enabled 
					        	//otherwise there may be more and they will all be displayed 
					        	
					        	$status =	get_config('block_ilp',$plugin_file.'_pluginstatus');
					        	
					        	$status	=	(!empty($status)) ?  $status: ILP_DISABLED;
					        	
					        	if (!empty($misplug) & $status == ILP_ENABLED ) {
									$misoverviewplugins[]	=	$pluginobj;
					        	}
					        }
					    }
					}
				}
			}
			
			
	
			//if the user has the capability to view others ilp and this ilp is not there own 
			//then they may change the students status otherwise they can only view 
			
			//get all enabled reports in this ilp
			$reports		=	$this->dbc->get_reports(ILP_ENABLED);
			
			//RPM 2013-02-11 set our content to have default values, if we have content these will be overwritten.
			$ilptargets = "No Targets to show";
			$ilptutorreview = "No Tutor reviews added";
			//RPM end
			
			//we are going to output the add any reports that have state fields to the percentagebar array 
			if (!empty($reports) ) {
				foreach ($reports as $r) {
					if ($this->dbc->has_plugin_field($r->id,'ilp_element_plugin_state')) {
	
						$reportinfo				=	new stdClass();
						$reportinfo->total		=	$this->dbc->count_report_entries($r->id,$this->student_id);
                        $reportinfo->actual		=	$this->dbc->count_report_entries_with_state($r->id,$this->student_id,ILP_STATE_PASS);
                        //retrieve the number of entries that have the not counted state
                        $reportinfo->notcounted	=	$this->dbc->count_report_entries_with_state($r->id,$this->student_id,ILP_STATE_NOTCOUNTED);

						 //if total_possible is empty then there will be nothing to report
		    	        if (!empty($reportinfo->total)) {
                            $reportinfo->total     =   $reportinfo->total -  $reportinfo->notcounted;
		    	        	//calculate the percentage
		    	        	$reportinfo->percentage	=	$reportinfo->actual/$reportinfo->total	* 100;
		    	        
		    	        	$reportinfo->name	=	$r->name;
	
		    	        	$percentagebars[]	=	$reportinfo;
		    	        }
						
					}
					
					//RPM 2013-02-11 build up additional content to show on the student info page
					//This is the list of targets in descending order and the latest tutor review entry
					
					if ($r->name == "Tutor Review") {
					
						//RPM - copy and paste from ilp_dashboard_reports_tab.php
						
						$reportentries	=	$this->dbc->get_user_report_entries($r->id,$this->student_id);
						$reportfields		=	$this->dbc->get_report_fields_by_position($r->id);
						//start buffering output
						ob_start();
						
						$icon =	(!empty($r->binary_icon)) ? $CFG->wwwroot."/blocks/ilp/iconfile.php?report_id=".$r->id : $CFG->wwwroot."/blocks/ilp/pix/icons/defaultreport.gif";
					
						$icon = "<img id='reporticon' class='icon_med' alt='$r->name ".get_string('reports','block_ilp')."' src='$icon' />";
						
						echo "<h2>{$icon}Latest Tutor Review</h2>";
						
						//create the entries list var that will hold the entry information
						$entrieslist	=	array();

						if (!empty($reportentries)) {
							
							// RPM - This method used to loop through using foreach but we are only interested in the first entry
							
							$entry = array_shift($reportentries);
							

							//TODO: is there a better way of doing this?
							//I am currently looping through each of the fields in the report and get the data for it
							//by using the plugin class. I do this for two reasons it may lock the database for less time then
							//making a large sql query and 2 it will also allow for plugins which return multiple values. However
							//I am not naive enough to think there is not a better way!

							$entry_data	=	new stdClass();

							//get the creator of the entry
							$creator				=	$this->dbc->get_user_by_id($entry->creator_id);

							//get comments for this entry
							$comments				=	$this->dbc->get_entry_comments($entry->id);

							//
							$entry_data->creator		=	(!empty($creator)) ? fullname($creator)	: get_string('notfound','block_ilp');
							$entry_data->created		=	userdate($entry->timecreated);
							$entry_data->modified		=	userdate($entry->timemodified);
							$entry_data->user_id		=	$entry->user_id;
							$entry_data->entry_id		=	$entry->id;

							
							//does this report allow users to say it is related to a particular course
							$has_courserelated	=	(!$this->dbc->has_plugin_field($r->id,'ilp_element_plugin_course')) ? false : true;

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

							foreach ($reportfields as $field) {

								//get the plugin record that for the plugin
								$pluginrecord	=	$this->dbc->get_plugin_by_id($field->plugin_id);

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

							include($CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs/ilp_dashboard_reports_tab.html');

						} else {

							echo get_string('nothingtodisplay');

						}

						// load custom javascript
						$module = array(
							'name'      => 'ilp_dashboard_reports_tab',
							'fullpath'  => '/blocks/ilp/classes/dashboard/tabs/ilp_dashboard_reports_tab.js',
							'requires'  => array('yui2-dom', 'yui2-event', 'yui2-connection', 'yui2-container', 'yui2-animation')
						);

						// js arguments
						$jsarguments = array(
							'open_image'   => $CFG->wwwroot."/blocks/ilp/pix/icons/switch_minus.gif",
							'closed_image' => $CFG->wwwroot."/blocks/ilp/pix/icons/switch_plus.gif",
						);

						// initialise the js for the page
						$PAGE->requires->js_init_call('M.ilp_dashboard_reports_tab.init', $jsarguments, true, $module);


						$ilptutorreview = ob_get_contents();
						ob_end_clean();
					}
					
					
					if ($r->name == "Targets") {
						
						//RPM - another copy and paste from ilp_dashboard_reports_tab.php
						
						$reportentries	=	$this->dbc->get_user_report_entries($r->id,$this->student_id);
						$reportfields = $this->dbc->get_report_fields_by_position($r->id);
						
						$access_report_editreports	= false;
						
						//start buffering output
						ob_start();
						
						$icon =	(!empty($r->binary_icon)) ? $CFG->wwwroot."/blocks/ilp/iconfile.php?report_id=".$r->id : $CFG->wwwroot."/blocks/ilp/pix/icons/defaultreport.gif";
					
						$icon = "<img id='reporticon' class='icon_med' alt='$r->name ".get_string('reports','block_ilp')."' src='$icon' />";

						echo "<h2>{$icon}{$r->name}</h2>";
						
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
								$creator				=	$this->dbc->get_user_by_id($entry->creator_id);

								//get comments for this entry
								$comments				=	$this->dbc->get_entry_comments($entry->id);

								//
								$entry_data->creator		=	(!empty($creator)) ? fullname($creator)	: get_string('notfound','block_ilp');
								$entry_data->created		=	userdate($entry->timecreated);
								$entry_data->modified		=	userdate($entry->timemodified);
								$entry_data->user_id		=	$entry->user_id;
								$entry_data->entry_id		=	$entry->id;

								
								//does this report allow users to say it is related to a particular course
								$has_courserelated	=	(!$this->dbc->has_plugin_field($r->id,'ilp_element_plugin_course')) ? false : true;

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

								foreach ($reportfields as $field) {

									//get the plugin record that for the plugin
									$pluginrecord	=	$this->dbc->get_plugin_by_id($field->plugin_id);

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

								include($CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs/ilp_dashboard_reports_tab.html');

							}
						} else {

							echo get_string('nothingtodisplay');

						}

						// load custom javascript
						$module = array(
							'name'      => 'ilp_dashboard_reports_tab',
							'fullpath'  => '/blocks/ilp/classes/dashboard/tabs/ilp_dashboard_reports_tab.js',
							'requires'  => array('yui2-dom', 'yui2-event', 'yui2-connection', 'yui2-container', 'yui2-animation')
						);

						// js arguments
						$jsarguments = array(
							'open_image'   => $CFG->wwwroot."/blocks/ilp/pix/icons/switch_minus.gif",
							'closed_image' => $CFG->wwwroot."/blocks/ilp/pix/icons/switch_plus.gif",
						);

						// initialise the js for the page
						$PAGE->requires->js_init_call('M.ilp_dashboard_reports_tab.init', $jsarguments, true, $module);
						

						$ilptargets = ob_get_contents();
						ob_end_clean();
					}
					//RPM End
				}
			}
			
			//instantiate the percentage bar class in case there are any percentage bars
			$pbar	=	new ilp_percentage_bar();
			
			
			//we need to buffer output to prevent it being sent straight to screen
			ob_start();
			
			
			
			require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/plugins/'.$this->directory.'/ilp_dashboard_student_info.html');
			
			//$learnercontact->set_data(1);
			
			//echo $learnercontact->display();
			
			//pass the output instead to the output var
			$pluginoutput = ob_get_contents();
			
			
			
			ob_end_clean();
			
			
			return $pluginoutput;
			
		} else {
			//the student was not found display and error 
			print_error('studentnotfound','block_ilp');
		}
		
		
		
		
	}
	
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	function userstatus_select($selected_value =null)	{
		global	$USER, $CFG, $PARSER;


		$statusitems	=	$this->dbc->get_user_status_items();
		
		if (!empty($statusitems)) {
			$selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_RAW);
			$tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_RAW);
			$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);
			$form	= "<form action='{$CFG->wwwroot}/blocks/ilp/actions/save_userstatus.php' method='GET' id='studentstatusform' >";
					
			$form	.=	"<input type='hidden' name='student_id' id='student_id' value='{$this->student_id}' >";
			$form	.=	"<input type='hidden' name='course_id' id='course_id' value='{$course_id}' >";
			$form	.=	"<input type='hidden' name='user_modified_id' id='user_modified_id' value='{$USER->id}' >";
			$form	.=	"<input type='hidden' name='ajax' id='ajax' value='false' >";
			$form	.=	"<input type='hidden' name='tabitem' id='tabitem' value='$tabitem' >";
			$form	.=	"<input type='hidden' name='selectedtab' id='selectedtab' value='$selectedtab' >";
			
			$form .= "<select id='select_userstatus'  name='select_userstatus' >";

			foreach ($statusitems	as  $s) {
				
				$selected	=	($s->id	==	$selected_value) ? 'selected="selected"' : '';
				
				$form .= "<option value='{$s->id}' $selected >{$s->name}</option>";
			}
			
			$form .= '</select>';
			
			$form .= '<input type="submit" value="Change Status" id="studentstatussub" />';
			
			$form .= '</form>';
		} else {

			$form	=	"<span id='studentstatusform'>";
			
			$form	.= 'STATUS ITEMS NOT SET PLEASE CONTACT ADMIN';
			
			$form 	.= '</span>';
			
		}
		
		
		
		
		return $form;
		
	}
	
	
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	function language_strings(&$string) {
        $string['ilp_dashboard_student_info_plugin'] 					= 'student info plugin';
        $string['ilp_dashboard_student_info_plugin_name'] 				= 'student info';
	        
        return $string;
    }
	
	
	
	
	
	
	
	
}
