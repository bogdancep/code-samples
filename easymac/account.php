<?php
class Account extends CI_Controller {

	protected $email_cookie;
	
	public function __construct() {
		parent::__construct();

		$this->load->helper('url');
		$this->load->helper('cookie');
		$this->load->helper('form');
		$this->load->library('form_validation');
		
		$this->load->model('SiteUsersModel');
		$this->load->model('MiscModel');
		$this->email_cookie = get_cookie('easylogged');
	}

	public function index()	{

		if (strlen($this->email_cookie) > 0) {
			$data = $this->MiscModel->setInitValues('Profil', 'profil');
			$user_data = $this->SiteUsersModel->getOneUser(array('user_token' => $this->email_cookie));

			if ($this->SiteUsersModel->checkAccountValid($user_data['user_id']) == FALSE) {
				$data['msg_error'] = 'Contul inca nu a fost validat! Va rugam verificati casuta de e-mail cu care v-ati inregistrat ';
				$data['msg_error'] = $data['msg_error'] . 'sau cereti <a href="'.START_PAGE.'account/act_mail.html"><strong>retrimiterea</strong></a> e-mailului de activare a contului.';
			}
			$data['user'] = $user_data;
			$user_display = array(0 => $user_data['user_username']);
			if (strlen($user_data['user_firstname']) > 0) 
				$user_display[] = $user_data['user_firstname'];
			if (strlen($user_data['user_firstname']) > 0 && strlen($user_data['user_lastname']))
				$user_display[] = $user_data['user_firstname'] . ' ' . $user_data['user_lastname'];
			$data['displays'] = $user_display;

			$this->MiscModel->loadViews($this, 'account/profil', $data, TRUE);

		} else {
			$data = $this->MiscModel->setInitValues('Acces neautorizat', 'noaccess');
			$this->MiscModel->loadViews($this, 'noaccess', $data);
		} 
	}

	public function user_details($p_username){
		
		$this->load->model('AdsModel');
		$data = $this->MiscModel->setInitValues('Anunturi utilizator '.$p_username, 'userads');

		$user = $this->SiteUsersModel->getOneUser(array('user_username' => $p_username));
		if ($user === FALSE) {
			$data['msg'] = 'Din pacate userul <strong>'.$p_username.'</strong> nu a putut fi gasit.';
			$this->MiscModel->loadViews($this, 'message', $data);
		} else {
			$user_ads = $this->AdsModel->get_ads(0, 0, 1, $user['user_id']);
			$data['ads_list'] = $this->MiscModel->ads_add_infos($user_ads['ads']);
			$data['total_ads'] = $user_ads['total_rows'];
			$data['user'] = $user;

			$data['user']['user_registered'] = date('d F Y', strtotime($data['user']['user_registered']));
			if ($data['user']['user_lastlogin'] == '0000-00-00 00:00:00') {
				$data['user']['user_lastlogin'] = '';
			} else {
				$data['user']['user_lastlogin'] = date('d F Y', strtotime($data['user']['user_lastlogin']));
			}

			$agr_status = $this->AdsModel->get_ads_grouped('ad_status', $user['user_id']);
			$data['all_ads'] = 0;
			foreach ($agr_status as $agr_item) {
				$data['all_ads'] = $data['all_ads'] + $agr_item['no_ads'];
			}

			$this->MiscModel->loadViews($this, 'adsmain_view', $data, TRUE);
		}
	}

	public function user_dashboard($repost = 0) {

		$this->load->model('AdsModel');

		$user = $this->SiteUsersModel->getOneUser(array('user_token' => $this->email_cookie));
		if ($user === FALSE) {
			redirect('login/', 'refresh');
		}
		$data = $this->MiscModel->setInitValues('Anunturi utilizator '.$user['user_username'], 'userads');

		$del_id = $this->input->post('del_id', TRUE);
		if (isset($del_id) && $del_id > 0) {
			if ($this->AdsModel->is_users_ad($del_id, $user['user_id']) == true) {
				$this->AdsModel->set_status($del_id, STATUS_DELETED);
				$data['msg_ok'] = 'Anuntul a fost sters cu succes.';
			} else {
				$data['msg_error'] = 'Anuntul nu a putut fi sters.';
			}
		}
		if ($repost > 0) {
			if ($this->AdsModel->is_users_ad($repost, $user['user_id']) == true) {
				$this->AdsModel->set_status($repost, STATUS_ACTIV);
				$data['msg_ok'] = 'Anuntul a fost repostat cu succes.';
			} else {
				$data['msg_error'] = 'Anuntul nu a putut fi repostat.';
			}
		}

		$user_ads = $this->AdsModel->get_ads(0, 0, -1, $user['user_id']);

		$data['ads'] = $this->MiscModel->ads_add_infos($user_ads['ads']);
		$data['user'] = $user;

		$this->MiscModel->loadViews($this, 'account/user_ads', $data, TRUE);
	}

	public function saveprofil()  {
		
		if (strlen($this->email_cookie) > 0) {

			$data = $this->MiscModel->setInitValues('Profil', 'profil');
			$user_data = $this->SiteUsersModel->getOneUser(array('user_token' => $this->email_cookie));

			$user = array();
			$user['user_username'] = $this->input->post('user_name', TRUE);
			$user['user_displayname'] = $this->input->post('display_name', TRUE);
			$user['user_firstname'] = $this->input->post('first_name', TRUE);
			$user['user_email'] = $user_data['user_email'];
			$user['user_lastname'] = $this->input->post('last_name', TRUE);
			$user['user_phone1'] = $this->input->post('phone1', TRUE);
			$user['user_phone2'] = $this->input->post('phone2', TRUE);
			$user['user_url'] = $this->input->post('user_url', TRUE);

			$data['user'] = $user;
			$user_display = array(0 => $user['user_username']);
			if (strlen($user['user_firstname']) > 0) 
				$user_display[] = $user['user_firstname'];
			if (strlen($user['user_firstname']) > 0 && strlen($user['user_lastname']))
				$user_display[] = $user['user_firstname'] . ' ' . $user['user_lastname'];
			$data['displays'] = $user_display;

			if ($this->SiteUsersModel->checkExistingUsername($user_data['user_id'], $user_data['user_username']) === FALSE)
				$data['msg_error'] = '<strong>Username-ul</strong> ales exista deja. Va rugam alegeti altul.';
			
			if ($this->form_validation->run() === FALSE) {	/* erori la completarea campurilor */
				
				$this->MiscModel->loadViews($this, 'account/profil', $data, TRUE);

			} else {

				if ($this->SiteUsersModel->updateCurrentUser($user_data['user_id'], $user) === FALSE) {
					$data['msg_error'] = 'Informatiile nu au putut fi salvate! Va rugam reincercati.';
				} else {
					$data['msg_info'] = 'Informatiile au fost salvate cu succes!';
				}

				$this->MiscModel->loadViews($this, 'account/profil', $data, TRUE);

			}
		} else {
			$data = $this->MiscModel->setInitValues('Acces neautorizat', 'noaccess');
			$this->MiscModel->loadViews($this, 'noaccess', $data);
		} 
	}

	public function newaccount()	{

		$this->load->database();
		
		$data = $this->MiscModel->setInitValues('Cont nou', 'newaccount');
		$this->form_validation->set_rules('user_email', '<b>Email</b>', 'trim|xss_clean|required|valid_email|is_unique[easy_users.user_email]');

		$data['lastpage'] = $this->input->post('lastpage', TRUE);

		if (strlen($this->input->post('fromheader', TRUE)) > 0) {
			redirect('account/newaccount', 'location');
		}

		if ($this->form_validation->run() === FALSE) {	/* erori la email */
		
			$this->MiscModel->loadViews($this, 'account/newaccount', $data);
				
		} else {
				
			$this->form_validation->set_rules('pass1', '<b>Parola</b>', 'trim|xss_clean|required|min_length[5]|callback_matchpass');
				
			if ($this->form_validation->run() === FALSE) {	/*erori la parola */
		
				$this->MiscModel->loadViews($this, 'account/newaccount', $data);
					
			} else {	/***** cont creat cu succes *********/
					
				$email = $this->input->post('user_email', TRUE);
				$password = $this->input->post('pass1', TRUE);

				$pass_encrypt = $this->MiscModel->encryptString($password, FALSE);
				$user_token = $this->MiscModel->encryptString($email, TRUE);

				if ($this->SiteUsersModel->setNewUser($email, $pass_encrypt, $user_token) === FALSE){
					$data['is_logged'] = FALSE;
					$data['title'] = 'Eroare la crearea contului';
					$data['msg'] = 'Din pacate contul nu a putut fi creat. Va rugam sa ne contactati pe adresa de email '.MAIL_OFFICE;

					$this->MiscModel->loadViews($this, 'message', $data);
				} else {
					$this->load->model('EmailModel');

					set_cookie('easylogged', $user_token, 3600);

					$data['display_name'] = substr($email, 0, strpos($email, '@'));
					$data['is_logged'] = TRUE;
					$data['mail_sent'] = $this->EmailModel->sendActivationMail($email, $user_token);
					$data['lastpage'] = $this->input->post('lastpage', TRUE);

					$this->MiscModel->loadViews($this, 'account/acreated', $data);
				}
			}
		}
	}

	public function activate($code){
		$data = $this->MiscModel->setInitValues('Activare cont', 'activate');

		if ($this->SiteUsersModel->getOneUser(array('activ_token' => $code)) !== FALSE) {

			$data['title'] = 'Contul a fost activat!';
			$data['msg'] = '<p>Felicitari, contul dvs. a fost activat! De acum incolo va puteti bucura de toate functionalitatile ' . 
					'siteului <b><a href="'.START_PAGE.'">'.WWW_SHORT.'</a></b>!</p>';
			$data['msg'] .= '<p><b>Veti beneficia de:</b></p>';
			$data['msg'] .= '- Posibilitate de postare de anunturi cu valabilitate initiala 30 de zile;';
			$data['msg'] .= '<br/>- Posibilitate de incarcare a 3 poze;';
			$data['msg'] .= '<br/>- Posibilitate de a modifica anunturile postate;';
			$data['msg'] .= '<br/>- Posibilitate de a urmari accesarile anuntului;';
			$data['msg'] .= '<br/>- Posibilitate de a reinnoi anuntul.';

			$this->SiteUsersModel->setStatusValid($code);

		} else {
			$data['title'] = 'Contul nu a putut fi activat.';
			$data['msg'] = 'Contul nu a putut fi activat. Va rugam sa reincercati sau sa ne contactatipe adresa de mail <b>'.MAIL_OFFICE.'</b>.';
		}

		$this->MiscModel->loadViews($this, 'message', $data);
	}
	
	public function act_mail()	{
		$this->load->model('EmailModel');

		$user_data = $this->SiteUsersModel->getOneUser(array('user_token' => $this->email_cookie));
		$mail_sent = $this->EmailModel->sendActivationMail($user_data['user_email'], $user_data['activ_token']);
		
		if ($mail_sent === TRUE){
			$data = $this->MiscModel->setInitValues('E-mail trimis cu succes', 'message');
			$data['msg'] = 'E-mailul a fost trimis cu succes! Va rugam sa verificati casuta de email si apoi sa va activati contul.';
		} else {
			$data = $this->MiscModel->setInitValues('Eroare trimitere e-mail', 'message');
			$data['msg'] = 'E-mailul nu a putut si trimis. Va rugam sa <a href="'.START_PAGE.'account/act_mail.html"><strong>reincercati</strong></a>.';
		}
		$this->MiscModel->loadViews($this, 'message', $data);
	}

	public function changepass() {

		$user_data = $this->SiteUsersModel->getOneUser(array('user_token' => $this->email_cookie));

		if ($user_data !== FALSE) {
			$data = $this->MiscModel->setInitValues('Schimbare parola', 'changepass');

			if ($this->input->post('updpass', TRUE) == 1) {
				$old_pass = $this->input->post('pass0', TRUE);
				$new_pass = $this->input->post('pass1', TRUE);
				$confirm_pass = $this->input->post('pass2', TRUE);

				if (strlen($old_pass) == 0 || strlen($new_pass) == 0 || strlen($confirm_pass) == 0) {
					$data['msg_error'] = 'Te rugam sa completezi toate campurile!';
				} else {
					$old_pass = $this->MiscModel->encryptString($old_pass, FALSE);
					if ($this->SiteUsersModel->checkUserPass($user_data['user_id'], $old_pass) === FALSE) {
						$data['msg_error'] = 'Parola actuala nu este corecta!';
					} else {
						if ($new_pass == $confirm_pass) {
							if (strlen($new_pass) < 5) {
								$data['msg_error'] = 'Parola trebuie sa aiba minim 5 caractere!';
							} else {
								$new_pass = $this->MiscModel->encryptString($new_pass, FALSE);
								$this->SiteUsersModel->setNewPasssword($user_data['user_id'], $new_pass);
								$data['msg_ok'] = 'Parola schimbata cu succes!';
							}
						} else {
							$data['msg_error'] = 'Parolele nu sunt identice!';
						}
					}
				}
			}
			$this->MiscModel->loadViews($this, 'account/changepass', $data, TRUE);

		} else {
			$data = $this->MiscModel->setInitValues('Acces neautorizat', 'noaccess');
			$this->MiscModel->loadViews($this, 'noaccess', $data);
		}
	}

	/************************ Parola pierduta ****************************************/
	public function lostpass() {
		
		$data = $this->MiscModel->setInitValues('Parola pierduta', 'lostpass');
		$this->form_validation->set_rules('user_email', '<b>Email</b>', 'trim|xss_clean|required|valid_email|callback_email_exists');

		if ($this->form_validation->run() === FALSE) {	/* erori la email */
			
			$this->MiscModel->loadViews($this, 'account/lostpass', $data);
			
		} else {
			$this->load->model('EmailModel');

			$email = $this->input->post('user_email', TRUE);
			$recover_token = $this->MiscModel->encryptString(time(), TRUE);
			
			if ($this->SiteUsersModel->setRecoverToken($email, $recover_token) === TRUE) {
				$msg = 'Parola contului dvs. a fost resetata.';
				if ($this->EmailModel->sendRecoverPassMail($email, $recover_token) === TRUE){
					$msg = $msg . ' Veti primi instructiuni pentru resetarea parolei pe adresa email.';
				} else {
					$msg = $msg . ' Mailul nu a putut fi trimis. Va rugam reincercati!';
				}
			} else {
				$msg = 'Parola nu a putut fi resetata. Va rugam reincercati!';
			}
			$msg = $msg . '<br/><br/><a href="'.START_PAGE.'/login.html">Inapoi la pagina de login.</a>';
			$data['msg'] = $msg;
			$this->MiscModel->loadViews($this, 'message', $data);
		}
	}

	public function recover($p_token) {
		$data = $this->MiscModel->setInitValues('Recuperare parola', 'recover');
		$user = $this->SiteUsersModel->getOneUser(array('reset_pass' => $p_token));
		if ($user === FALSE) {
			$data['msg'] = 'Din pacate codul de resetare a parolei este invalid. ';
			$data['msg'] .= 'Va rugam sa reincercati sau sa ne contactati.';
			
			$this->MiscModel->loadViews($this, 'message', $data);
		} else {
			$data['token'] = $p_token;
			$this->MiscModel->loadViews($this, 'account/recoverpass_view', $data);
		}
	}

	public function recoveraction() {
		
		$data = $this->MiscModel->setInitValues('Recuperare parola', 'recover');
		$pass1 = $this->input->post('pass1', TRUE);
		$pass2 = $this->input->post('pass1', TRUE);

		$this->form_validation->set_rules('pass1', '<b>Parola</b>', 'trim|xss_clean|required|min_length[5]|callback_matchpass');
		if ($this->form_validation->run() === FALSE) {	/* erori la email */
		
			$this->MiscModel->loadViews($this, 'account/recoverpass_view', $data);
				
		} else {
			$pass_encrypt = $this->MiscModel->encryptString($pass1, FALSE);
			$token = $this->input->post('recovertoken', TRUE);
			$user_data = $this->SiteUsersModel->getOneUser(array('reset_pass' => $token));
			if ($this->SiteUsersModel->setNewPasssword($user_data['user_id'], $pass_encrypt) === FALSE) {
				$data['msg'] = 'Parola nu a putut fi schimbata. Va rugam reincercati.';
			} else {
				$data['msg'] = 'Parola a fost schimbata cu succes!';
			}
			$this->MiscModel->loadViews($this, 'message', $data);
		}

	}

	function matchpass($pass1) {	
		
		if ($pass1 !== $this->input->post('pass2', TRUE)) {
			$this->form_validation->set_message('matchpass', 'Parolele nu se potrivesc!');
			return FALSE;
		}
		
		return TRUE;
	}

	function email_exists($p_email) {
		
		$user = $this->SiteUsersModel->getOneUser(array('user_email' => $p_email));
		if ($user === FALSE) {
			$this->form_validation->set_message('email_exists', 'Emailul nu exista! Va rugam reincercati.');
			return FALSE;
		}
		
		return TRUE;
	}

}
/* End of file account.php */
/* Location: ./application/controllers/account.php */
/* Last Update: 20 July 2014 */