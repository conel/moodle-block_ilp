<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_itemlist.php');

class ilp_element_plugin_ddsta extends ilp_element_plugin_itemlist{
	
	public $tablename;
	public $data_entry_tablename;
	public $items_tablename;
	protected $selecttype;	//1 for single, 2 for multi
	public $emailnotify;	//0 for no, 1 for yes
	protected $id;		//loaded from pluginrecord
	
    /**
     * Constructor
     */
    function __construct() {
    	$this->tablename 			= "block_ilp_plu_ddsta";
    	$this->data_entry_tablename = "block_ilp_plu_ddsta_ent";
		$this->items_tablename 		= "block_ilp_plu_ddsta_items";
		//RPM the items table listed here is actually a view of all active staff user accounts in moodle
	parent::__construct();
    }
	
	
	/**
     * TODO comment this
     * beware - different from parent method because of variable select type
     * radio and other single-selects inherit from parent
     */
    public function load($reportfield_id) {
		$reportfield		=	$this->dbc->get_report_field_data($reportfield_id);	
		if (!empty($reportfield)) {
			$this->reportfield_id	=	$reportfield_id;
			$this->plugin_id	=	$reportfield->plugin_id;
			$plugin			=	$this->dbc->get_form_element_plugin($reportfield->plugin_id);
			$pluginrecord		=	$this->dbc->get_form_element_by_reportfield($this->tablename,$reportfield->id);
			if (!empty($pluginrecord)) {
				$this->id				=	$pluginrecord->id;
				$this->label			=	$reportfield->label;
				$this->description		=	$reportfield->description;
				$this->req				=	$reportfield->req;
				$this->position			=	$reportfield->position;
				$this->selecttype		=	$pluginrecord->selecttype;
				$this->emailnotify		=	$pluginrecord->emailnotify;

			}
			//RPM
			//print_object($pluginrecord);
			//die();
		}
		return false;	
    }	

	

    public function audit_type() {
        return get_string('ilp_element_plugin_ddsta_type','block_ilp');
    }
    
    /**
    * function used to return the language strings for the plugin
    */
    function language_strings(&$string) {
        $string['ilp_element_plugin_ddsta'] 				= 'Select';
        $string['ilp_element_plugin_ddsta_type'] 			= 'Staff select box';
        $string['ilp_element_plugin_ddsta_description'] 	= 'A Staff drop-down selector';
		$string[ 'ilp_element_plugin_ddsta_optionlist' ] 	= 'Option List';
		$string[ 'ilp_element_plugin_ddsta_single' ] 		= 'Single select';
		$string[ 'ilp_element_plugin_ddsta_multi' ] 		= 'Multi select';
		$string[ 'ilp_element_plugin_ddsta_typelabel' ] 	= 'Select type (single only)';
		$string[ 'ilp_element_plugin_ddsta_existing_options' ] 	= 'existing options';
		$string[ 'ilp_element_plugin_error_item_key_exists' ]	= 'The following key already exists in this element';
		$string[ 'ilp_element_plugin_error_duplicate_key' ]		= 'Duplicate key';
		$string[ 'emailnotify' ]		= 'Send an email notification to this person when the report is updated.';
		$string[ 'warninglabel' ]		= 'WARNING!';
		$string[ 'warningdescription' ]		= 'This field currently only works correctly if there is one instance per report';
		$string[ 'ilp_element_plugin_ddsta_editnotify' ] 		= 'An ILP entry where you are included has been updated';
	        
        return $string;
    }

	/**
	* handle user input
	**/
	 public	function entry_specific_process_data($reportfield_id,$entry_id,$data) {
	
		/*
		* parent method is fine for simple form element types
		* dd types will need something more elaborate to handle the intermediate
		* items table and foreign key
		*/
		return $this->entry_process_data($reportfield_id,$entry_id,$data); 	
	 }

	 /** RPM OVERRIDE
    * read rows from item table and return them as array of key=>value
    * @param int $reportfield_id
    * @param string $field - extra field to read from items table: used by ilp_element_plugin_state
    * @param bool	$useid	should ids be returned as the value or should the actual value 
    *
    */
	protected function get_option_list( $reportfield_id, $field=false, $useid=true ){
		$outlist = array();
		if( $reportfield_id )	{
			$objlist = $this->dbc->get_optionlist_ddsta($reportfield_id , $this->tablename, $field );
			
			foreach( $objlist as $obj )	{
				//obj->value will only be returned if specifically requested (this should only befor value editing)
				//in all other cases id should be returned
				$value	=	(!empty($useid)) ? $obj->id : $obj->value;
				$outlist[ $value ] = $obj->name;
			}
		}
		return $outlist;
	}
	 
		/** RPM OVERRIDE
		*
	    * this function saves the data entered on a entry form to the plugins _entry table
		* the function expects the data object to contain the id of the entry (it should have been
		* created before this function is called) in a param called id. 
		* as this is a select element, possibly a multi-select, we have to allow
		* for the possibility that the input is an array of strings
	    */
	  	public	function entry_process_data($reportfield_id,$entry_id,$data) {
	 	
	  		$result	=	true;
	  		
		  	//create the fieldname
			$fieldname =	$reportfield_id."_field";
	
		 	//get the plugin table record that has the reportfield_id 
		 	$pluginrecord	=	$this->dbc->get_plugin_record($this->tablename,$reportfield_id);
		 	if (empty($pluginrecord)) {
		 		print_error('pluginrecordnotfound');
		 	}
		 	
		 	//check to see if a entry record already exists for the reportfield in this plugin
            $multiple = !empty( $this->items_tablename );
		 	$entrydata 	=	$this->dbc->get_pluginentry_ddsta($this->tablename, $entry_id,$reportfield_id,$multiple);
		 	
		 	//if there are records connected to this entry in this reportfield_id 
			if (!empty($entrydata)) {
				//delete all of the entries
                $extraparams = array( 'audit_type' => $this->audit_type() );
				foreach ($entrydata as $e)	{
					$this->dbc->delete_element_record_by_id( $this->data_entry_tablename, $e->id, $extraparams );
				}
			}  
		 	
			//create new entries
			$pluginentry			=	new stdClass();
            $pluginentry->audit_type = $this->audit_type();
			$pluginentry->entry_id  = 	$entry_id;
	 		$pluginentry->value		=	( !empty( $data->$fieldname ) ) ? $data->$fieldname : '' ;
	 		//pass the values given to $entryvalues as an array
	 		$entryvalues	=	(!is_array($pluginentry->value)) ? array($pluginentry->value): $pluginentry->value;
	 		
			
			
	 		foreach ($entryvalues as $ev) {
                if( !empty( $ev ) ){
		 			//$state_item				=	$this->dbc->get_state_item_id($this->tablename,$pluginrecord->id,$ev, $this->external_items_keyfield, $this->external_items_table );
					$state_item				=	$this->dbc->get_state_item_id($this->tablename,0,$ev, $this->external_items_keyfield, false );
		 		    $pluginentry->parent_id	=	$state_item->id;	
		 			$pluginentry->value 	= 	$state_item->value;
					//print_object($pluginentry);
					//die();
					$result					= 	$this->dbc->create_plugin_entry($this->data_entry_tablename,$pluginentry);
                }
	 		}
	 	
		
		//RPM 06032013
		//adding in code to notify the staff member with a moodle message if the emailnotify flag is set
		//code taken from edit_entrycomment.php
		
		if ($this->emailnotify == 1) {
			//notify the selected user that an update has been made on this report entry if it is not them updating it
            if ($USER->id != $state_item->value)   {
				global $CFG;
				global $USER;
			
				$reportsviewtab             =   $this->dbc->get_tab_plugin_by_name('ilp_dashboard_reports_tab');
				$reportfield 				= 	$this->dbc->get_report_field_data($this->reportfield_id);
				$report						=	$this->dbc->get_report_by_id($reportfield->report_id);
				
                $reportstaburl              =   (!empty($reportsviewtab)) ?  "&selectedtab={$reportsviewtab->id}&tabitem={$reportsviewtab->id}:{$report->id}" : "";
			
                $message                    =   new stdClass();
                $message->component         =   'block_ilp';
                $message->name              =   'ilp_comment';
                $message->subject           =   get_string('ilp_element_plugin_ddsta_editnotify','block_ilp',$report);
                $message->userfrom          =   $this->dbc->get_user_by_id($USER->id);
                $message->userto            =   $this->dbc->get_user_by_id($state_item->value);
                $message->fullmessage       =   'An ILP '.$report->name.' report where you are mentioned has been updated';
                $message->fullmessageformat =   FORMAT_PLAIN;
                $message->contexturl        =   $CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$data->user_id}{$reportstaburl}#repanchor";
                $message->contexturlname    =   get_string('viewreport','block_ilp');

				
                if (stripos($CFG->release,"2.") !== false) {
                    message_send($message);
                }   else {
                    require_once($CFG->dirroot.'/message/lib.php');
                    message_post_message($message->userfrom, $message->userto,$message->fullmessage,$message->fullmessageformat,'direct');
                }				
				//print_object($message);
				//die();
            }
		}
			return	$result;
	 }

	 
	 /** RPM OVERRIDE
	  * places entry data for the report field given into the entryobj given by the user 
	  * 
	  * @param int $reportfield_id the id of the reportfield that the entry is attached to 
	  * @param int $entry_id the id of the entry
	  * @param object $entryobj an object that will add parameters to
	  */
	 public function entry_data( $reportfield_id,$entry_id,&$entryobj ){
	 	//this function will suffix for 90% of plugins who only have one value field (named value) i
	 	//in the _ent table of the plugin. However if your plugin has more fields you should override
	 	//the function 
	 	
		//default entry_data 	
		$fieldname	=	$reportfield_id."_field";
	 	
	 	
	 	$entry	=	$this->dbc->get_pluginentry_ddsta($this->tablename,$entry_id,$reportfield_id,true);
 
		if (!empty($entry)) {
		 	$fielddata	=	array();

		 	//loop through all of the data for this entry in the particular entry		 	
		 	foreach($entry as $e) {
		 		$fielddata[]	=	$e->parent_id;
		 	}
		 	
		 	//save the data to the objects field
	 		$entryobj->$fieldname	=	$fielddata;
	 	}
	 }
	 
	 /** RPM OVERRIDE
	  * places entry data formated for viewing for the report field given  into the  
	  * entryobj given by the user. By default the entry_data function is called to provide
	  * the data. Any child class which needs to have its data formated should override this
	  * function. 
	  * 
	  * @param int $reportfield_id the id of the reportfield that the entry is attached to 
	  * @param int $entry_id the id of the entry
	  * @param object $entryobj an object that will add parameters to
	  */
	  public function view_data( $reportfield_id,$entry_id,&$entryobj ){
	  		$fieldname	=	$reportfield_id."_field";
	 		$entry	=	$this->dbc->get_pluginentry_ddsta($this->tablename,$entry_id,$reportfield_id,true);
			if (!empty($entry)) {
		 		$fielddata	=	array();
		 		$comma	= "";
			 	//loop through all of the data for this entry in the particular entry		 	
			 	foreach($entry as $e) {
			 		$entryobj->$fieldname	.=	"{$comma}{$e->value}";
			 		$comma	=	",";
			 	}
	 		}	
	  }


	/** RPM OVERRIDE
    * this function returns the mform elements that will be added to a report form
	*
    */
    public function entry_form( &$mform ) {
    	
    	//create the fieldname
    	$fieldname	=	"{$this->reportfield_id}_field";
    	
		//definition for user form
		$optionlist = $this->get_option_list( $this->reportfield_id );

    	if (!empty($this->description)) {
    		$mform->addElement('static', "{$fieldname}_desc", $this->label, strip_tags(html_entity_decode($this->description),ILP_STRIP_TAGS_DESCRIPTION));
    		$this->label = '';
    	} 

		$optionlist = array_reverse($optionlist, true); 
		$optionlist[''] = 'Select..'; 
		$optionlist = array_reverse($optionlist, true); 
		
    	//text field for element label
        $select = &$mform->addElement(
            'select',
            $fieldname,
            $this->label,
	    	$optionlist,
            array('class' => 'form_input')
        );
		
		
		/* get the first teacher alphabetically on the course */

		try {
		// get the list of everyone with teacher capabilities in this context
		$course_id = $_GET["course_id"];
		$course_context = get_context_instance(CONTEXT_COURSE, $course_id);
		$teachers = get_users_by_capability($course_context, 'block/ilp:viewotherilp', 'u.id, CONCAT_WS(" ", u.firstname, u.lastname) AS name', 'u.firstname');
	
		reset($teachers);
		$first_key = key($teachers);
		}
		catch (Exception $e) {
			}
		
		//if we dont have any teachers on the course (!) then take the current staff member
		if ($first_key <> "") {
			$select->setselected($first_key);
		}
		else {
			global $USER;
			$select->setselected("$USER->id");
		}
	
				
        if( ILP_OPTIONMULTI == $this->selecttype ){
			$select->setMultiple(true);
		}
        
        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'client');
        $mform->setType('label', PARAM_RAW);

    }

	  
}

