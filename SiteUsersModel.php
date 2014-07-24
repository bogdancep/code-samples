<?php
class SiteUsersModel extends CI_Model {

	protected $table_name = 'easy_users';
	protected $id_field = 'user_id';

	const STATUS_NEVALIDAT	= 0;
	const STATUS_VALIDAT	= 1;
	
	public function __construct() {
		$this->load->database();
	}

	/* get user data by params passed */
	public function getOneUser($p_params) {

		$q = $this->db->get_where($this->table_name, $p_params);
		
		if ($q->num_rows() == 1) {
			return $q->row_array();
		} else {
			return FALSE;
		}
	}

	/******************** get all users *********************************/
	public function getUsers($p_limit = 0, $p_start = 0) {

		$limit = '';
		if ($p_limit > 0) $limit = 'LIMIT '.$p_start.', '.$p_limit;
		$q = $this->db->query("SELECT * FROM ".$this->table_name." ORDER BY user_registered DESC ".$limit);
		$users = $q->result_array();
		$q->free_result();

		return $users;
	}
	
	/********************* login verification ***************************/
	public function checkLogin($p_email, $p_passcrp) {
		
		$this->db->select('user_displayname');
		$query = $this->db->get_where($this->table_name, array('user_email' => $p_email, 'user_pass' => $p_passcrp));
		
		if ($query->num_rows() == 1) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/********************* check if current password if correct ***************************/
	public function checkUserPass($p_userid, $p_pass) {

		$params = array('user_id' => $p_userid, 'user_pass' => $p_pass);
		$q = $this->db->get_where($this->table_name, $params);

		if ($q->num_rows() == 1) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/******************* check if account is valid *************************/
	public function checkAccountValid($p_userid) {
		
		$this->db->select('user_status');
		$q = $this->db->get_where($this->table_name, array('user_id' => $p_userid, 'user_status' => self::STATUS_VALIDAT));
		
		if ($q->num_rows() == 1) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/******************* check if username already exists *************************/
	public function checkExistingUsername($p_userid, $p_username) {
		$sql = "SELECT * FROM ".$this->table_name." WHERE user_id <> '".$p_userid."' AND user_username = '".$p_username."'";
		$q = $this->db->query($sql);
		$no_results = $q->num_rows();
		$q->free_result();

		if ($no_results == 0)
			return TRUE;
		else 
			return FALSE;
	}
	
	/******************* save new user *************************************/
	public function setNewUser($p_email, $p_pass, $p_token) {
	
		$data = array(
				'user_username' => substr($p_email, 0, strpos($p_email, '@')),
				'user_email' => $p_email,
				'user_pass' => $p_pass,
				'user_token' => $p_token,
				'activ_token' => $p_token,
				'user_displayname' => substr($p_email, 0, strpos($p_email, '@')),
				'user_registered' => date("Y-m-d H:i:s", time())
				
		);
			
		return $this->db->insert($this->table_name, $data);
	}

	/******************* edit user *************************************/
	public function updateCurrentUser($p_userid, $p_userdata) {
	
		$data = array(
				'user_username' => $p_userdata['user_username'],
				'user_displayname' => $p_userdata['user_displayname'],
				'user_firstname' => $p_userdata['user_firstname'],
				'user_lastname' => $p_userdata['user_lastname'],
				'user_phone1' => $p_userdata['user_phone1'],
				'user_phone2' => $p_userdata['user_phone2'],
				'user_url' => $p_userdata['user_url']
				
		);
		$this->db->where('user_id', $p_userid);
		return $this->db->update($this->table_name, $data);
	}

	/************* if lost password, set a recovery token ***************************************/
	public function setRecoverToken($p_email, $token) {

		$data = array(
			'reset_pass' => $token
		);
			
		$this->db->where('user_email', $p_email);
		$q = $this->db->update($this->table_name, $data);
		
		if ($q === FALSE) return FALSE;
		else return TRUE;
	}

	/************************* validate new account *******************************/
	public function setStatusValid($p_code) {
		
		$data = array( 'user_status' => '1' );
		$this->db->where('activ_token', $p_code);
		return $this->db->update($this->table_name, $data); 
	}

	/************************* change password *******************************/
	public function setNewPasssword($p_userid, $p_password) {
		
		$data = array( 'user_pass' => $p_password );
		$this->db->where('user_id ', $p_userid);
		return $this->db->update($this->table_name, $data); 
	}

	/******* when successfully logged in user, set a user token and update last login time ****/
	public function updateForLogin($user_email, $p_code) {
		
		$last_login = date('Y-m-d H:i:s');
		$data = array( 
					'user_lastlogin' 	=> $last_login,
					'user_token'		=> $p_code
				 );
		$this->db->where('user_email', $user_email);
		return $this->db->update($this->table_name, $data); 
	}
}

/* End of file SiteUsersModel.php */
/* Location: ./application/models/SiteUsersModel.php */
/* Last Update: 17 July 2014 */ 