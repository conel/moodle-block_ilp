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

		$mform->addElement(
			'textarea',
			'optionlist',
			get_string( 'ilp_element_plugin_ddsta_optionlist', 'block_ilp' ),
			array('class' => 'form_input')
	        );

		//admin must specify at least 1 option, with at least 1 character
        $mform->addRule('optionlist', null, 'minlength', 1, 'client');

		$typelist = array(
			ILP_OPTIONSINGLE => get_string( 'ilp_element_plugin_ddsta_single' , 'block_ilp' ),
			ILP_OPTIONMULTI => get_string( 'ilp_element_plugin_ddsta_multi' , 'block_ilp' )
		);
		
		$mform->addElement(
			'select',
			'selecttype',
			get_string( 'ilp_element_plugin_ddsta_typelabel' , 'block_ilp' ),
			$typelist,
			array('class' => 'form_input')
		);
		
		$mform->addElement(
			'static',
			'existing_options',
			get_string( 'ilp_element_plugin_ddsta_existing_options' , 'block_ilp' ),
			''
		);
	  }
	
	 protected function specific_validation($data) {
	 	$data = (object) $data;
	 	return $this->errors;
	 }
	 
	 function definition_after_data() {
	 	
	 }
	
}
