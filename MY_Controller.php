<?php

class MY_Controller Extends CI_Controller {

	public $useraccess;
	public function __construct(){
		parent::__construct();
		$this->load->helper('file');
	}
	/*
		@param string $class_action
		@param array  $params
	*/
	public function _remap($class_action = '',$params = '')
	{	

		// var_dump($this->session);exit;
		//filepath for our access rights
		$file_path = APPPATH.'/third_party/access/'.$this->session->userdata['group_access_file_name'].'.access';
		$data = read_file($file_path);
		$accessCode = json_decode(base64_decode($data),true);
		$this->useraccess = $accessCode;


		if($class_action == 'index'){
			$access = $this->_check_module_access();
			$this->index($params);
		}else if(strpos($class_action, '_') == false){			
			//for non modulated classes
			$this->$class_action($params);

		}else{

			 
			$this->current_module = $class_action;

			$json_error_return_status_header  = 403 ;
			$json_error_return['status']  = false;
			$json_error_return['message'] = 'You are not allowed to access this page.';
			
						 			
			//parse the class and action
			$_m_ = $this->_parse_class_method($class_action);
			//checks if current user has access
			$access = $this->_check_module_access($_m_);
			
			if($access){
				if($_m_['status'] === true){
					//returned bot params module and action
					$this->load->library($_m_['module']);
					if(method_exists($_m_['class'],$_m_['action'])){
						$this->_check_module_access();
						$this->$_m_['class']->$_m_['action']($params);

					}else{						
						if(!$this->input->is_ajax_request()){
							show_404();							
						}else{
							$json_error_return['code'] = 10002;
							Template::json($json_error_return,$json_error_return_status_header);
						}
					}
				}else{
					if(!$this->input->is_ajax_request()){
						show_404();
					}else{
						$json_error_return['code'] = 10003;
						Template::json($json_error_return,$json_error_return_status_header);
					}
				}
			}else{
				$c_get_deb = $this->input->get($this->_DEBUG_VARIABLE);
				$c_pst_deb = $this->input->post($this->_DEBUG_VARIABLE);
				if( $c_get_deb || $c_pst_deb AND ($c_get_deb == $this->_DEBUG_PASSWORD) || ($c_pst_deb == $this->_DEBUG_PASSWORD) ){


				}else{
					if(!$this->input->is_ajax_request()){
						show_error('You are not allowed to access this page.',403,'User Permission Invalid!');					
					}else{
						$json_error_return['code'] = 10004;
						Template::json($json_error_return,$json_error_return_status_header);
					}
				}
			}
		}
	}

	private function _parse_class_method($class){
		//try to strip as much url data in our class
		$_C = explode('_', str_replace(' ','',trim(urldecode(strip_tags($class)))));
		$class = isset($_C[0]) ? $_C[0] : NULL;
		$method = isset($_C[1]) ? $_C[1] : NULL;
		
		$module = 'modules/'.$class;
		$_parsed_method = str_replace('-', '_', trim($method));

		$mod['module'] = $module;
		$mod['action'] = $_parsed_method;
		$mod['class']  = $class;
		$mod['status'] = false;

		if($method && $class){
			$mod['status'] = true;
		}

		return $mod;
	}

	private function _check_module_access($module_action = false)
	{
		$access = $this->useraccess;
		if(!$module_action){
			Template::set('data',['useraccess'=>$access]);
			return;
		}

		$accessed_module = strtolower($module_action['class']);
		$accessed_action = strtolower($module_action['action']);

		if($access !== false || $access !== NULL)
		{
			$user_modules_assigned = array_keys($access['modules']);
			
			if( isset($access['modules'][$accessed_module]) ){
				// $access
			}else{
				if($this->input->is_ajax_request()){
					Template::json(array('status'=>false,'message'=>'Process failed, Invalid permission. #9981'));
				}else{
					show_error('You are not allowed to access this page. #9981');	
				}
				exit;
			}

			$user_modules_actions_assigned = array_keys($access['modules'][$accessed_module]);

			if(!in_array($accessed_module, array_map('strtolower',$user_modules_assigned))){
				$access = false;
			}else{
				if(!in_array($accessed_action,array_map('strtolower',$user_modules_actions_assigned))){
					$access = false;
				}else{
					$access = true;
				}
			}

			return $access;
		}else{
			show_error('Unable to access permissions table, Permission error.');
		}
	}

	private function _build_menu(){
		$menu_path = APPPATH.'/third_party/data/menudata.bin';
	}
}
