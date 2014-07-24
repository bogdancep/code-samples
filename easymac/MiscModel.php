<?php
class MiscModel extends CI_Model {
	
	public function __construct() {
		//$this->load->database();
	}

	public function encryptString($p_input, $random = FALSE) {
		if ($random === TRUE) {
			return sha1(sha1($p_input.S_USER_SALT.time()));
		} else {
			return sha1(sha1($p_input.S_USER_SALT));
		}
		
	}

	public function setInitValues($title, $page) {
	
		$p_data['title'] = $title;
		$p_data['page'] = $page;
	
		$p_data['is_logged'] = FALSE;
		$p_data['display_name'] = null;
		$user_cookie = $this->input->cookie('easylogged', TRUE);
		if (strlen($user_cookie) > 0) {
			$user_data = $this->SiteUsersModel->getOneUser(array('user_token' => $user_cookie));
			if ($user_data !== FALSE) {
				$user_display = $user_data['user_displayname'];
				set_cookie('easylogged', $user_cookie, 3600);
				$p_data['display_name'] = $user_display;
				$p_data['is_logged'] = TRUE;
			}
		}
		return $p_data;
	}

	public function loadViews($controller, $main_view, $p_data, $user_account = FALSE) {
		$controller->load->view('templates/site_header', $p_data);
		$controller->load->view($main_view, $p_data);
		$controller->load->view('templates/side_search');
		if ($user_account === TRUE)
			$controller->load->view('templates/side_user_account', $p_data);
		if (defined('ENVIRONMENT') && ENVIRONMENT == 'production') {
			$this->load->view('templates/side_fbbox');
		}
		$controller->load->view('templates/site_footer', $p_data);
	}
	
	/*------- return array with pagination links ---------*/
	function getPagination($p_crtpage, $p_nopages, $p_searchitem = '', $p_categ = ''){
		$ret = array();

		$ret['goback'] = '';
		if ($p_crtpage > 1) {
			$ret['goback'] = START_PAGE.'ads/page/'.($p_crtpage-1).'.html';

			if (strlen($p_searchitem) > 0) 
				$ret['goback'] = START_PAGE.'ads/page/'.($p_crtpage-1).'/?s='.$p_searchitem;

			if (strlen($p_categ) > 0) 
				$ret['goback'] = START_PAGE.'ads/page/'.($p_crtpage-1).'/?c='.$p_categ;
		}

		$ret['page1'] = '';
		if ($p_crtpage > 2){ 
			$ret['page1'] = START_PAGE;

			if (strlen($p_searchitem) > 0) 
				$ret['page1'] = START_PAGE.'?s='.$p_searchitem;

			if (strlen($p_categ) > 0) 
				$ret['page1'] = START_PAGE.'?c='.$p_categ;
		}

		$ret['crt_min1'] = '';
		if ($p_crtpage > 1){ 
			$ret['crt_min1'] = START_PAGE.'ads/page/'.($p_crtpage-1).'.html';

			if (strlen($p_searchitem) > 0) 
				$ret['crt_min1'] = START_PAGE.'ads/page/'.($p_crtpage-1).'/?s='.$p_searchitem;

			if (strlen($p_categ) > 0) 
				$ret['crt_min1'] = START_PAGE.'ads/page/'.($p_crtpage-1).'/?c='.$p_categ;
		}

		$ret['crt_plus1'] = '';
		if ($p_crtpage < $p_nopages){ 
			$ret['crt_plus1'] = START_PAGE.'ads/page/'.($p_crtpage+1).'.html';

			if (strlen($p_searchitem) > 0) 
				$ret['crt_plus1'] = START_PAGE.'ads/page/'.($p_crtpage+1).'/?s='.$p_searchitem;

			if (strlen($p_categ) > 0) 
				$ret['crt_plus1'] = START_PAGE.'ads/page/'.($p_crtpage+1).'/?c='.$p_categ;
		}

		$ret['last_page'] = '';
		if ($p_crtpage  < $p_nopages - 1){ 
			$ret['last_page'] = START_PAGE.'ads/page/'.($p_nopages).'.html';

			if (strlen($p_searchitem) > 0) 
				$ret['last_page'] = START_PAGE.'ads/page/'.($p_nopages).'/?s='.$p_searchitem;

			if (strlen($p_categ) > 0) 
				$ret['last_page'] = START_PAGE.'ads/page/'.($p_nopages).'/?c='.$p_categ;
		}

		$ret['gofw'] = ''; 
		if ($p_nopages > $p_crtpage) {
			$ret['gofw'] = START_PAGE.'ads/page/'.($p_crtpage+1).'.html';

			if (strlen($p_searchitem) > 0) 
				$ret['gofw'] = START_PAGE.'ads/page/'.($p_crtpage+1).'/?s='.$p_searchitem;

			if (strlen($p_categ) > 0) 
				$ret['gofw'] = START_PAGE.'ads/page/'.($p_crtpage+1).'/?c='.$p_categ;
		}

		return $ret;
	}

	function adsAddInfos($p_ads) {
		$ret_ads = $p_ads;

		foreach ($ret_ads as $key => &$ad_item) {

			$ad_item['link'] = url_title($ad_item['ad_title'],'dash',TRUE);
			$ad_item['link'] = START_PAGE.'ads/'.$ad_item['link'].'/'.$ad_item['id'].'.html';

			if (strlen($ad_item['ad_description']) > 150) {
				$ad_item['ad_description'] = substr($ad_item['ad_description'], 0, 150) . '... <a href="'.$ad_item['link'].'">descriere completa</a>';
			}
			$ad_item['thumb_pict'] =  $this->AdsModel->get_single_pict($ad_item['id']);
			if ($ad_item['thumb_pict'] === FALSE) {
				$ad_item['thumb_pict'] = 'no-thumb.jpg';
				$ad_item['thumb_path'] = START_PAGE.'public/images/'.$ad_item['thumb_pict'];
			} else {
				$year = substr($ad_item['ad_date_submit'],0,4);
				$ad_item['thumb_path'] = START_PAGE.'public/uploads/'.$year.'/'.$ad_item['thumb_pict'];
			}
			$ad_item['bkgcolor'] = ''; $ad_item['action1'] = '';
			if ($ad_item['ad_status'] == STATUS_ACTIV) {
				$ad_item['bkgcolor'] = 'style="background-color: #DDFFA1;"';
				$ad_item['action1'] = '<a href="'.START_PAGE.'ads/edit/'.$ad_item['id'].'.html"><img src="'.base_url().'public/images/edit.png" title="Modifica" /></a>';
			}
			if ($ad_item['ad_status'] == STATUS_EXPIRED) {
				$ad_item['bkgcolor'] = 'style="font-size:10px;background-color: #F0F0F0;"';
				$ad_item['action1'] = '<a href="'.START_PAGE.'ads/repost/'.$ad_item['id'].'.html"><img src="'.base_url().'public/images/repost.png" title="Repune" /></a>';
			}
			//if ($ad_item['ad_status'] == 2) $ad_item['bkgcolor'] = 'style="background-color: #FCFFB7;"';
		}

		return $ret_ads;
	}

	function addClassInfo($p_ads) {
		$ret_ads = $p_ads;

		foreach ($ret_ads as $key => &$ad_item) {
			$ad_item['class_status'] = '';
			if ($ad_item['ad_status'] == STATUS_DELETED) $ad_item['class_status'] = 'status_deleted';
			if ($ad_item['ad_status'] == STATUS_ACTIV) $ad_item['class_status'] = 'status_activ';
			if ($ad_item['ad_status'] == STATUS_EXPIRED) $ad_item['class_status'] = 'status_expired';
			if ($ad_item['ad_status'] == STATUS_WAIT_APRV) $ad_item['class_status'] = 'status_wait_aprv';
		}

		return $ret_ads;
	}
}

/* End of file MiscModel.php */
/* Location: ./application/models/MiscModel.php */
/* Last Update: 19 July 2014 */