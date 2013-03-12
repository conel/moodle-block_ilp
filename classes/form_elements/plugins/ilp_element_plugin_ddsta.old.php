<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_itemlist.php');

class ilp_element_plugin_ddsta extends ilp_element_plugin_itemlist{
	
	public $tablename;
	public $data_entry_tablename;
	public $items_tablename;
	protected $selecttype;	//1 for single, 2 for multi
	protected $id;		//loaded from pluginrecord
	
    /**
     * Constructor
     */
    function __construct() {
    	$this->tablename 			= "block_ilp_plu_ddsta";
    	$this->data_entry_tablename = "block_ilp_plu_ddsta_ent";
		$this->items_tablename 		= "block_ilp_plu_ddsta_items";
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

			}
		}
		return false;	
    }	

	

    public function audit_type() {
        return get_string('ilp_element_plugin_dd_type','block_ilp');
    }
    
    /**
    * function used to return the language strings for the plugin
    */
    function language_strings(&$string) {
        $string['ilp_element_plugin_ddsta'] 				= 'Select';
        $string['ilp_element_plugin_ddsta_type'] 			= 'A drop-down staff selector';//'select box';
        $string['ilp_element_plugin_ddsta_description'] = 'A drop-down staff selector';
		$string[ 'ilp_element_plugin_ddsta_optionlist' ] 	= 'Option List';
		$string[ 'ilp_element_plugin_ddsta_single' ] 		= 'Single select';
		$string[ 'ilp_element_plugin_ddsta_multi' ] 		= 'Multi select';
		$string[ 'ilp_element_plugin_ddsta_typelabel' ] 			= 'Select type (single/multi)';
		$string[ 'ilp_element_plugin_ddsta_existing_options' ] 	= 'existing options';
		$string[ 'ilp_element_plugin_error_item_key_exists' ]	= 'The following key already exists in this element';
		$string[ 'ilp_element_plugin_error_duplicate_key' ]		= 'Duplicate key';
	        
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
	    * Overridden to stop looking up the ID from an items table as we dont use one here
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
					$state_item				=	$this->dbc->get_state_item_id($this->tablename,$pluginrecord->id,$ev, $this->external_items_keyfield, $this->external_items_table );
					
					$pluginentry->parent_id =$state_item->id;	
		 			$pluginentry->value 	= 	$state_item->value;
					
					//$pluginentry->parent_id	=	$data->$fieldname; // we dont use the items table, use the user ID as the parent too
		 			//$pluginentry->value 	= 	$data;
					
					$result					= 	$this->dbc->create_plugin_entry($this->data_entry_tablename,$pluginentry);
				
                }
	 		}
	 	
			return	$result;
	 }
		


	/** RPM Override
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
			print_object($fielddata);
	 	}
	 }

		
}

