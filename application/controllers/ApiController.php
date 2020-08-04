<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH.'/libraries/REST_Controller.php';
use api\libraries\REST_Controller;

class ApiController extends REST_Controller {
    public function __construct(){
        parent::__construct();
		// Your own constructor code
		$this->load->model('ApiModel');
		$this->load->model('JwtModel');
    }
    
    function index_get(){
        $data = array("mgs"=>"this is controller");
        $this->response($data);
	}

	function generateToken_post(){
		$item = json_decode(json_encode($this->post()));
        $input['user_name'] = $item->user_name;
		$input['company_name'] = $item->company_name;
		$token = $this->JwtModel->encodeToken($input);
		if($token){
			$status = parent::HTTP_OK;
			$response = ['status' => $status, 'token' => $token];
		}else{
			$status = parent::HTTP_UNAUTHORIZED;
			$response = ['status' => $status, 'msg' => 'Unauthorized Access!'];
		}
        $this->response($response);
	}

	function checkToken_post(){
		$item = json_decode(json_encode($this->post()));
        $input['user_name'] = $item->user_name;
		$input['company_name'] = $item->company_name;
		$checkToken = $this->ApiModel->check_token($input);
        $this->response($checkToken);
	}
	
	function getPay_get($no_akaun){		
		// Get all the headers
		$headers = $this->input->request_headers();
		if(isset($headers['Token'])){
			$token = $headers['Token'];
		}else{
			$token = false;
		}
		// Extract the token
		if($token){
			$authToken = $this->JwtModel->decodeToken($token);
			if($authToken){
				$response = $this->ApiModel->getPay($no_akaun);
			}else{
				$status = parent::HTTP_UNAUTHORIZED;
				$response = ['status' => $status, 'msg' => 'Unauthorized Access!'];
			}
		}else{
			$status = parent::HTTP_UNAUTHORIZED;
			$response = ['status' => $status, 'msg' => 'Unauthorized Access!'];
		}
		$this->response($response);
	}

	function updatePay_post(){
		$json = str_replace('[]','null',json_encode($this->post()));		
		$item = json_decode($json);
        $input['NO_AKAUN'] = $item->NO_AKAUN;
		$input['NAMA'] = $item->NAMA;
		$input['TARIKH_BAYAR'] = $item->TARIKH_BAYAR;
		$input['NO_RESIT'] = $item->NO_RESIT;
		$input['AMAUN'] = $item->AMAUN;
		$input['FLAG'] = $item->FLAG;
		$input['NO_RUJUKAN'] = $item->NO_RUJUKAN;
		$input['SALURAN'] = $item->SALURAN;
		$data = $this->ApiModel->updatePay($input);
		$mgs = array("mgs"=>$data);
        $this->response($mgs);
	}
}