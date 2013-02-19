<?PHP 

/*
 * @copyright &copy; 2007 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 1.0
 */

class block_ilp extends block_list {

    function init() {
        $this->title = get_string('blockname', 'block_ilp');
        $this->version = 2008053100;
    }

	function has_config() {
        return true;
    }

	function config_save($data) {
        // Default behavior: save all variables as $CFG properties
        $module = 'project/ilp';
        foreach ($data as $name => $value) {

            if ($name == 'ilp_target_grade' && $value != '') {
                // Need to insert new record(s) into mdl_targets
                $targets = explode(',', $value);
                foreach($targets as $target) {
                    $target_name = trim($target);

                    // Before inserting, check if a target with the same name already exists
                    if (!record_exists('targets', 'name', $target_name)) {
                        $data = new Object();
                        $data->id = 0;
                        $data->name = $target_name;
                        $data->date_created = time();
                        insert_record('targets', $data, false);
                    }
                }
            } else if (($name == 'target_rename' && $value != '' && $data->target_id_rename != '') && $data->ilp_target_grade == '') {
                $target_id = $data->target_id_rename; 
                $target_name = $value;
                // Update record
                if ($data = get_record('targets', 'id', $target_id)) {
                    $data->name = $target_name;
                    $data->date_modified = time();
                    // Update record
                    $success = update_record('targets', $data);
                }
            } else if ($name == 'target_id_to_delete' && $value != '') {
                // Delete this target
                $success = delete_records('targets', 'id', $value); 
            } else {
                set_config($name, $value, $module);
            }
        }
        return true;
	}
    function get_content() {

        global $CFG;
 		$module = 'project/ilp';
		$config = get_config($module);
        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        // the following 3 lines is need to pass _self_test();
        if (empty($this->instance->pageid)) {
            return '';
        }

		$access_isgod = 0 ;
        $access_isteacher = 0 ;
        $access_isstudent = 0 ;
        $access_isother = 0 ;
		
		if (has_capability('moodle/site:doanything', get_context_instance(CONTEXT_SYSTEM))) {  // are we god ?
        $access_isgod = 1 ;
    }

        if (!$currentcontext = get_context_instance(CONTEXT_COURSE, $this->instance->pageid)) {
            $access_isother = 1 ;
        } else {
            if (has_capability('block/ilp:viewclass',$currentcontext)) { // are we the teacher on the course ?
                $access_isteacher = 1 ;
            } elseif (has_capability('block/ilp:view',$currentcontext)) {  // are we a student on the course ?
                $access_isstudent = 1 ;
            }
        }
        $this->content = new object();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        $url = ($access_isstudent) ? $CFG->wwwroot.'/blocks/ilp/view.php' : $CFG->wwwroot.'/blocks/ilp/list.php' ;

        if (!$access_isother) {
            $url .= "?courseid=".$this->instance->pageid ;
        }
		
		if(!empty($config->ilp_user_guide_link) && $config->ilp_user_guide_link != 0){
			$this->content->items[] = '<a href="'.$config->ilp_user_guide_link.'" target="newWin">'.get_string('userguide','block_ilp').'</a>';
			$this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/info.gif" class="icon" alt="" />';
		}

		if ($access_isstudent) {
			$this->content->items[] = '<a href="'.$url.'">'.get_string('viewmyilp','block_ilp').'</a>';
			$this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/users.gif" class="icon" alt="" />';
		} else {
			$this->content->items[] = '<a href="'.$url.'">'.get_string('viewilps','block_ilp').'</a>';
			$this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/users.gif" class="icon" alt="" />';
			if ($this->instance->pageid != SITEID) {
				/*
				$this->content->items[] = '<a href="'.$CFG->wwwroot.'/mod/ilptarget/view_students.php?courseid='.$this->instance->pageid.'">'.get_string('modulenameplural','ilptarget').'</a>';
				$this->content->icons[] = '<img src="'.$CFG->pixpath.'/mod/ilptarget/icon.gif" class="icon" alt="" />';
				*/
				$this->content->items[] = '<a href="'.$CFG->wwwroot.'/mod/ilpconcern/view_students.php?courseid='.$this->instance->pageid.'">Reports</a>';
				$this->content->icons[] = '<img src="'.$CFG->pixpath.'/mod/ilpconcern/icon.gif" class="icon" alt="" />';
			}
			/*
			if($access_isgod || $access_isteacher) {
				$this->content->items[] = get_string('download_reports','block_ilp').':';
				$this->content->icons[] = '';
				$this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/ilp/reports.php?mode=user&amp;courseid='.$this->instance->pageid.'">'.get_string('user_reports','block_ilp').'</a>';
				$this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/users.gif" class="icon" alt="" />';
				if($access_isgod) {
					$this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/ilp/reports.php?mode=course">'.get_string('course_reports','block_ilp').'</a>';
					$this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/course.gif" class="icon" alt="" />';
				}	
			}
			*/
		
		}	

        return $this->content;

    }


    // my moodle can only have SITEID and it's redundant here, so take it away
    //function applicable_formats() {
     //   return array('all' => true, 'my' => false);
    //}

}

?>