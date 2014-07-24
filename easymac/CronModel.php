<?php
class CronModel extends CI_Model {

	public function __construct() {
		$this->db = $this->load->database('default', TRUE);
		if (defined('ENVIRONMENT') && ENVIRONMENT == 'production'){
			$this->db_old = $this->load->database('actual', TRUE);
		}
	}

	public function getOldUsers() {
		$sql = "SELECT * FROM em_users";
		if (defined('ENVIRONMENT') && ENVIRONMENT == 'production'){
			$q = $this->db_old->query($sql);
		} else {
			$q = $this->db->query($sql);
		}
		$results = $q->result_array();
		$q->free_result();

		return $results;
	}

	public function syncUsers($new_data) {

		$rows_ins = 0; $rows_upd = 0; $rows_not = 0;
		$result = array();

		foreach ($new_data as $key => $new_user) {

			$updates = array();
			foreach ($new_user as $key => $value) {
				if ($key != 'user_pass')
					$updates[] = $key . "='". $value . "' ";
			}
			
			$sql = "INSERT INTO easy_users (".implode(',', array_keys($new_user)).") VALUES ('".implode('\',\'',$new_user)."') 
					ON DUPLICATE KEY UPDATE ".implode(', ', $updates)." ";
			$this->db->query($sql);
			if ($this->db->affected_rows() == 1) 
				$rows_ins++;
			elseif ($this->db->affected_rows() == 2)
				$rows_upd++;
			elseif ($this->db->affected_rows() == 0)
				$rows_not++;
			if (($rows_ins + $rows_upd + $rows_not) % 500 == 0)
				echo date('H:i:s').': Processed '. ($rows_ins + $rows_upd + $rows_not).' rows.<br/>';
		}
		echo date('H:i:s').': Processed '. ($rows_ins + $rows_upd + $rows_not).' rows.<br/>';
		$result['inserted'] = $rows_ins;
		$result['updated'] = $rows_upd;
		$result['not_affected'] = $rows_not;

		return $result;
	}

	public function getOldListings($page, $limit) {
		$sql = "SELECT a.* , b.*, un.* FROM em_posts a
				INNER JOIN em_postmeta b ON b.post_id = a.ID
				INNER JOIN em_users uv ON uv.ID = a.post_author
				INNER JOIN easy_users un ON un.user_email = uv.user_email
				WHERE post_type =  'ad_listing' ORDER BY a.ID  LIMIT ".$page*$limit.", ".$limit." ";

		if (defined('ENVIRONMENT') && ENVIRONMENT == 'production'){
			$q = $this->db_old->query($sql);
		} else {
			$q = $this->db->query($sql);
		}
		$results = $q->result_array();
		$q->free_result();

		return $results;
	}

	public function getOldListNo () {
		$sql = "SELECT COUNT(*) no FROM em_posts a ";

		if (defined('ENVIRONMENT') && ENVIRONMENT == 'production'){
			$q = $this->db_old->query($sql);
		} else {
			$q = $this->db->query($sql);
		}
		$result = $q->row_array();
		$q->free_result();

		return $result;
	}

	public function syncListings($new_data) {

		$rows_ins = 0; $rows_upd = 0; $rows_not = 0;
		$result = array();

		foreach ($new_data as $key => $new_user) {

			$sql = "INSERT INTO ads_main (".implode(',', array_keys($new_user)).") VALUES ('".implode('\',\'',$new_user)."') 
					ON DUPLICATE KEY UPDATE id=id ";
			$this->db->query($sql);
			if ($this->db->affected_rows() == 1) 
				$rows_ins++;
			elseif ($this->db->affected_rows() == 2)
				$rows_upd++;
			elseif ($this->db->affected_rows() == 0)
				$rows_not++;
			if (($rows_ins + $rows_upd + $rows_not) % 500 == 0)
				echo date('H:i:s').': Processed '. ($rows_ins + $rows_upd + $rows_not).' rows.<br/>';
		}
		//echo date('H:i:s').': Processed '. ($rows_ins + $rows_upd + $rows_not).' rows.<br/>';
		$result['inserted'] = $rows_ins;
		$result['updated'] = $rows_upd;
		$result['not_affected'] = $rows_not;

		return $result;
	}
}

/* End of file cron_model.php */
/* Location: ./application/models/cron_model.php */
/* Last Update: 08 May 2014 */