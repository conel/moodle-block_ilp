<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_itemlist_mform.php');

class ilp_element_plugin_ddsta_mform  extends ilp_element_plugin_itemlist_mform {
	
	public $tablename;
	public $items_tablename;
	
	function __construct($report_id,$plugin_id,$creator_id,$reportfield_id=null) {
		parent::__construct($report_id,$plugin_id,$creator_id,$reportfield_id=null);
		$this->tablename = "block_ilp_plu_ddsta";
		$this->items_tablename = "block_ilp_plu_ddsta_items";
	}
	  	
	
	  protected function specific_definition($mform) {
		
		/**
		textarea element to contain the options the admin wishes to add to the user form
		admin will be instructed to insert value/label pairs in the following plaintext format:
		value1:label1\nvalue2:label2\nvalue3:label3
		or some such
		*/

		
		/* RPM removing some of these options as they dont apply to this field
		$mform->addElement(
			'textarea',
			'optionlist',
			get_string( 'ilp_element_plugin_dd_optionlist', 'block_ilp' ),
			array('class' => 'form_input')
	        );

		//admin must specify at least 1 option, with at least 1 character
        $mform->addRule('optionlist', null, 'minlength', 1, 'client');
		*/
		
		$typelist = array(
			ILP_OPTIONSINGLE => get_string( 'ilp_element_plugin_ddsta_single' , 'block_ilp' )
			//only single select
			//,ILP_OPTIONMULTI => get_string( 'ilp_element_plugin_dd_multi' , 'block_ilp' )
		);
		
		$mform->addElement(
			'select',
			'selecttype',
			get_string( 'ilp_element_plugin_ddsta_typelabel' , 'block_ilp' ),
			$typelist,
			array('class' => 'form_input')
		);
		
		/*
		$mform->addElement(
			'static',
			'existing_options',
			get_string( 'ilp_element_plugin_dd_existing_options' , 'block_ilp' ),
			''
		);
		*/
		
		//dropdown to state whether the email notification is required
        $select = $mform->addElement('selectyesno', 
        				   'emailnotify', 
        					get_string('emailnotify', 'block_ilp'),
							array('class' => 'form_input')
        );
		$mform->setType('emailnotify', PARAM_INT);
		$mform->setdefault('emailnotify',1);
		
		$mform->addElement('static', 'warning', get_string('warninglabel', 'block_ilp'),
		get_string('warningdescription', 'block_ilp'));
		
	  }
	
	/* RPM override from ilp_element_plugin_mform.php
	* dont bother validating
	*
	*/
	function validation($data) {
        $this->errors = array();
        /*
        //check that the field label does not already exist in this report
        if ($this->dbc->label_exists($data['label'],$data['report_id'],$data['id']))	{
        	$this->errors['label']	=	get_string('labelexistserror','block_ilp',$data);
        } 
                
        // now add fields specific to this type of evidence
        $this->specific_validation($data);
		*/
        return $this->errors;
    }
	
	
	 protected function specific_validation($data) {
	 	$data = (object) $data;
	 	return $this->errors;
	 }
	 
	 function definition_after_data() {
	 	
	 }



	/* RPM OVERRIDE - to allow for emailnotfify field to get updated too
	* take input from the management form and write the element info
	*/
	 protected function specific_process_data($data) {
		$optionlist = array();
		if( in_array( 'optionlist' , array_keys( (array) $data ) ) ){
			//dd type needs to take values from admin form and writen them to items table
			$optionlist = ilp_element_plugin_itemlist::optlist2Array( $data->optionlist );
		}
		//entries from data to go into $this->tablename and $this->items_tablename
	  	
	 	$plgrec = (!empty($data->reportfield_id)) ? $this->dbc->get_plugin_record($this->tablename,$data->reportfield_id) : false;
	 	
	 	if (empty($plgrec)) {
			//options for this dropdown need to be written to the items table
			//each option is one row
	 		$element_id = $this->dbc->create_plugin_record($this->tablename,$data);
		
			//$itemrecord is a container for item data
			$itemrecord = new stdClass();	
			$itemrecord->parent_id = $element_id;
			foreach( $optionlist as $key=>$itemname ){
				//one item row inserted here
				$itemrecord->value = $key;
				$itemrecord->name = $itemname;
	 			$this->dbc->create_plugin_record($this->items_tablename,$itemrecord);
			}
	 	} else {
	 		//get the old record from the elements plugins table 
	 		$oldrecord				=	$this->dbc->get_form_element_by_reportfield($this->tablename,$data->reportfield_id);
			$data_exists = $this->dbc->plugin_data_item_exists( $this->tablename, $data->reportfield_id );
			$element_id = $this->dbc->get_element_id_from_reportfield_id( $this->tablename, $data->reportfield_id );
			//$itemrecord is a container for item data
			$itemrecord = new stdClass();	
			$itemrecord->parent_id = $element_id;

			if( empty( $data_exists ) ){
				//no user data - go ahead and delete existing items for this element, to be replaced by the submitted ones in $data
				
				/* we dont delete list items as it is a view not a table
				$delstatus = $this->dbc->delete_element_listitems( $this->tablename, $data->reportfield_id );
				*/
					//if $delstatus false, there has been an error - alert the user
			} else {
				//user data has been submitted already - don't delete existing items, but add new ones if they are in $data
				//purge $optionlist of already existing item_keys
				//then it will be safe to write the items to the items table
				foreach( $optionlist as $key=>$itemname ){
					if( $this->dbc->listelement_item_exists( $this->items_tablename, array( 'parent_id' => $element_id, 'value' => $key ) ) ){
						//this should never happen, because it shouldn't have passed validation, but you never know
						unset( $optionlist[ $key ] );
						//alert the user
					}
				}
			}
			//now write fresh options from $data
			foreach( $optionlist as $key=>$itemname ){
				//one item row inserted here
				$itemrecord->value = $key;
				$itemrecord->name = $itemname;
		 		$this->dbc->create_plugin_record($this->items_tablename,$itemrecord);
			}
	
	 		//create a new object to hold the updated data
	 		$pluginrecord 			=	new stdClass();
	 		$pluginrecord->id		=	$oldrecord->id;
	 		//$pluginrecord->optionlist	=	$data->optionlist;
			$pluginrecord->selecttype 	= 	ILP_OPTIONSINGLE;
			$pluginrecord->emailnotify 	= 	$data->emailnotify;
	 		
			//RPM
			//print_object($this->tablename);
			//print_object($pluginrecord);
			//die();
			
			//update the plugin with the new data
	 		return $this->dbc->update_plugin_record($this->tablename,$pluginrecord);
	 	}
	 }


	 
}
