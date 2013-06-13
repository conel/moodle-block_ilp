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
		$sql = "select cc.id, cc.name, cc.coursecount, t.term_name, t.term_start_date, t.term_end_date, cs.*, cst.*, 0 is_course from mdl_course_categories cc
			inner join mdl_block_ilp_stats cs on cc.id = cs.categoryid
			inner join mdl_block_ilp_stats_term cst on cst.categoryid = cs.categoryid
			inner join mdl_terms t on t.id = cst.termid
			where cc.parent = ".$categoryid." and term_code=".$termnum."
			order by cst.termid, cc.sortorder;";
		
		//echo $sql;
		
		return $DB->get_records_sql($sql);
	
	}
	
	
	function get_all_stats ($termnum) {
		
		global $DB;
		
		//$sql = "select t.*, cc.*, cs.*, cst.* from mdl_course_categories cc
		$sql = "select cc.id, cc.name, cc.parent, t.term_name, from_unixtime(t.term_start_date, '%d/%m/%Y') term_start_date, from_unixtime(t.term_end_date, '%d/%m/%Y') term_end_date, cs.*, cst.* from mdl_course_categories cc
			inner join mdl_block_ilp_stats cs on cc.id = cs.categoryid
			inner join mdl_block_ilp_stats_term cst on cst.categoryid = cs.categoryid
			inner join mdl_terms t on t.id = cst.termid
			where term_code=".$termnum."
			order by cst.termid, cc.sortorder;";
		
		//echo $sql;
		
		return $DB->get_records_sql($sql);
	
	}
	
	
	function get_course_stats ($categoryid, $termnum) {
		
		global $DB;
		
		//appends an is_course flag to the records manually, used by reports.php draw_table method to change the way it links
		
		//$sql = "select t.*, cc.*, cs.*, cst.* from mdl_course_categories cc
		$sql = "select c.id, c.fullname name, cc.coursecount, t.term_name, t.term_start_date, t.term_end_date, cs.*, cst.*, 1 is_course from mdl_course_categories cc
			inner join mdl_course c on c.category = cc.id and cc.id = ".$categoryid."
			inner join mdl_block_ilp_stats_course cs on c.id = cs.courseid
			inner join mdl_block_ilp_stats_course_term cst on cst.courseid = cs.courseid
			inner join mdl_terms t on t.id = cst.termid
			where term_code=".$termnum."
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