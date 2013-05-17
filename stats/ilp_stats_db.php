<?php


/**
 * Database class for ILP stats methods
 *
 * @author Russell Morriss
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @version 1.0
 * See blocks/lpr/models/block_lpr_db.php for examples that can be adapted
 */
 
class ilp_stats_db {

	function get_category_stats ($categoryid, $termnum) {
		
		global $DB;
		
		//$sql = "select t.*, cc.*, cs.*, cst.* from mdl_course_categories cc
		$sql = "select cc.id, cc.name, cc.coursecount, t.term_name, t.term_start_date, t.term_end_date, cs.*, cst.* from mdl_course_categories cc
			inner join mdl_block_ilp_stats cs on cc.id = cs.categoryid
			inner join mdl_block_ilp_stats_term cst on cst.categoryid = cs.categoryid
			inner join mdl_terms t on t.id = cst.termid
			where cc.parent = ".$categoryid." and term_code=".$termnum."
			order by cst.termid, cc.sortorder;";
		
		//echo $sql;
		
		return $DB->get_records_sql($sql);
	
	}
	
	
	
	function get_last_update() {
		
		global $DB;
		
		//$sql = "select t.*, cc.*, cs.*, cst.* from mdl_course_categories cc
		$sql = "SELECT max(updated) max_updated FROM mdl_block_ilp_stats;";
		
		//echo $sql;
		
		return $DB->get_record_sql($sql);
	
	}

	
	function update_stats() {
		
		global $DB;
		
		//$sql = "select t.*, cc.*, cs.*, cst.* from mdl_course_categories cc
		$sql = "call upd_ilp_stats;";
		
		//echo $sql;
		
		//this will return the number of courses that have stats records that can be written to the log file
		return $DB->get_record_sql($sql);
	
	}
	
	

}

?>