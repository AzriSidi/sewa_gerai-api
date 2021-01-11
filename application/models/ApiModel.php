<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ApiModel extends CI_Model{

	public function getPay($no_akaun){
		$clmn_penyewa = 'INITCAP (a.nama) nama,a.status_batal,a.tangguh,';
		$clmn_sewaan = 'b.no_petak as no_petak,b.harta as kod_harta,';
		$clmn_kod_jns_sewaan = 'INITCAP (c.keterangan) as jenis_jualan,';
		$clmn_harta = 'INITCAP (d.keterangan) as harta,';
		$clmn_akn_smsa = 'e.TUNGGAKAN as tunggakan,e.SEWA_SEMASA as sewa_semasa,
						  e.BAYAR_LEBIH as bayar_lebih,';
		$clmn_prlu_byr = '(sewa_semasa + tunggakan - bayar_lebih) as jumlah_perlu_bayar,';
		$clmn_jns_hrta = 'f.kod_akaun as kod_akaun';
		
		$table_penyewa = 'GERAI.PENYEWA a';
		$table_sewaan = 'GERAI.SEWAAN b';
		$table_kod_jns_sewaan = 'GERAI.KOD_JENIS_SEWAAN c';
		$table_harta = 'GERAI.HARTA d';
		$table_akn_smsa = 'GERAI.AKAUN_SEMASA e';
		$table_jns_hrta = 'GERAI.JENIS_HARTA f';
		
		$this->db
			 ->select($clmn_penyewa.$clmn_sewaan.$clmn_kod_jns_sewaan.$clmn_harta.
					   $clmn_akn_smsa.$clmn_prlu_byr.$clmn_jns_hrta." from ".$table_penyewa,false)
			 ->join($table_sewaan,'a.KOD_AKAUN = b.KOD_AKAUN',null,false)
			 ->join($table_kod_jns_sewaan,'b.KOD_JENIS_SEWAAN=c.KOD',null,false)
			 ->join($table_harta,'d.KOD=b.HARTA',null,false)
			 ->join($table_akn_smsa,'e.NO_AKAUN=a.NO_AKAUN',null,false)
			 ->join($table_jns_hrta,'b.JENIS_HARTA=f.KOD',null,false)
			 ->where('a.NO_AKAUN',"'".$no_akaun."'",false)
			 ->where('(a.STATUS_BATAL IS NULL',null,false)
			 ->or_where("a.STATUS_BATAL='A'",null,false)			 
			 ->or_where("a.STATUS_BATAL='T')",null,false)
			 ->where('a.TANGGUH IS NULL',null,false)
			 ->order_by("d.KETERANGAN", "desc",false)
			 ->order_by("c.KETERANGAN", "desc",false)
			 ->order_by("b.NO_PETAK", "desc",false);
			
		$query = $this->db->get();
		
		if ($query->num_rows() > 0){
			$row = $query->row();
					
			if($row->STATUS_BATAL == "X"){
				$status = "BATAL";
			}elseif($row->STATUS_BATAL == null OR $row->STATUS_BATAL == "A"){
				$status = "AKTIF";
			}elseif($row->STATUS_BATAL == "T"){
				$status = "TANGGUH";
			}if($row->TANGGUH == "T"){
				$status = "TANGGUH";
			}
			
			unset($row->STATUS_BATAL,$row->TANGGUH);
			$status_arr = array("STATUS" => $status);

			if($row->JUMLAH_PERLU_BAYAR <> 0){
				$checkResit = $this->checkResit($no_akaun);
			}else{
				$checkResit = array(
					"TARIKH" => "",
					"MASA" => "",
					"NO_RESIT" => ""
				);
			}

			$result = array_merge((array)$row,$status_arr,(array)$checkResit);
		}else{
			$result['mgs'] = "No Data";
		}

		$this->db->close();
		return $result;
	}

	public function checkResit($no_akaun){
		$clmn = 'TO_CHAR(tarikh) as TARIKH,TO_CHAR(masa) as MASA,no_resit';
		$clmn2 = "TO_CHAR(tarikh_bayar,'dd-mm-yyyy') as TARIKH,TO_CHAR(tarikh_bayar,'hh:mi:ss') as MASA,no_resit";
		
		$this->db
			 ->select($clmn.' from kutipan.kutipan',false)
			 ->where('no_akaun',"'".$no_akaun."'",false)
			 ->where("(status <> 'B'",null,false)
			 ->or_where('status IS NULL)',null,false)
			 ->where('post IS NULL',null,false);
		$query1 = $this->db->get_compiled_select();

		$this->db
			 ->select($clmn.' from hasil.ebayar_trxid',false)
			 ->where('no_akaun',"'".$no_akaun."'",false)
			 ->where('flag',"'SUCCESSFUL'",false)
			 ->where('status_kutipan IS NULL',null,false);
		$query2 = $this->db->get_compiled_select();

		$this->db
			 ->select($clmn2.' from gerai.bayaran_terkini',false)
			 ->where('no_akaun',"'".$no_akaun."'",false)
			 ->where('flag',"'SUCCESSFUL'",false)
			 ->where('tarikh_post IS NULL',null,false);
		$query3 = $this->db->get_compiled_select();
		
		$query = $this->db->query($query1." UNION ".$query2." UNION ".$query3)->row();
		
		// $query = $this->db->query($query1." UNION ".$query2)->row();
		if($query != null){
			return $query;
		}else{
			$query = array(
				"TARIKH" => "",
				"MASA" => "",
				"NO_RESIT" => ""
			);
			return $query;
		}		
	}

	public function api_users($input,$token){		
		$query = $this->db->query("SELECT *  FROM gerai.api_users WHERE
						  user_name ="."'".$input["user_name"]."'"."AND 
						  company_name = "."'".$input["company_name"]."'".
						  "AND auth = '1'");
		$count_row = $query->num_rows();
		if ($count_row > 0) {
			$mgs = false;
		}else{
			$this->db
			 ->set('user_name', "'".$input['user_name']."'", FALSE)
			 ->set('company_name', "'".$input['company_name']."'", FALSE)
			 ->set('token', "'".$token."'", FALSE)
			 ->set('auth', '1', FALSE)
			 ->insert('gerai.api_users', null, FALSE);
			 $mgs = true;
		}
		return $mgs;
	}
	
	public function check_token($input){
		$this->db->select("*")
        		 ->from('GERAI.API_USERS')
				 ->where("USER_NAME",$input['user_name'])
				 ->where("COMPANY_NAME",$input['company_name']);
		$query = $this->db->get();
		if ($query->num_rows() > 0){
			$row = $query->row();
			if($row->AUTH=='1'){
				$row->AUTH = 'Authorized!';
			}else{
				$row->AUTH = 'Unauthorized!';
			}
			$tokenArr = array('token'=>$row->TOKEN,'auth'=>$row->AUTH);
			return $tokenArr;
		}else{
			return array('mgs'=>'No Token');
		}
	}

	public function auth_token($input){
		$mgs = false;
		$this->db->select("*")
        		 ->from('GERAI.API_USERS')
				 ->where("USER_NAME",$input->user_name)
				 ->where("COMPANY_NAME",$input->company_name)
				 ->where("AUTH",'1');
		$query = $this->db->get();
		if ($query->num_rows() > 0){
			$mgs = true;
		}
		return $mgs;
	}

	public function updatePay($input){
		$mgs = false;			
		if(is_null($input['NAMA'])){
			unset($input['NAMA']);
			$checkInput = in_array(NULL, $input, true);
			if(!$checkInput){
				$input['NAMA'] = NULL;
				$mgs = true;
			}
		}else{
			$checkInput = in_array(NULL, $input, true);
			if(!$checkInput){
				$mgs = true;
			}
		}
		
		if($mgs){
			$tkh_byr = $input['TARIKH_BAYAR'];
			$mgs = false;

			$this->db->where('NO_AKAUN', $input['NO_AKAUN']);
			$query = $this->db->get('GERAI.PENYEWA');
			if ($query->num_rows() > 0){
				$this->db->set('NO_AKAUN', $input['NO_AKAUN'])
					->set('NAMA', $this->db->escape($input['NAMA']))
					->set('TARIKH_BAYAR', "to_date('$tkh_byr','DDMMYYYY HH24MISS')",FALSE)
					->set('NO_RESIT', $input['NO_RESIT'])
					->set('AMAUN', $input['AMAUN'])
					->set('FLAG', $input['FLAG'])
					->set('NO_RUJUKAN', $input['NO_RUJUKAN'])
					->set('SALURAN', $input['SALURAN'])
					->insert("GERAI.BAYARAN_TERKINI");
					$mgs = true;
			}
		}
		return $mgs;
	} 

	public function saveLogApi($log){
		if($log['request'] != ''){
			$_REQUEST = $log['request'];
		}else{
			$_REQUEST = null;
		}

		$set = "NO_AKAUN,URL_API,USER_NAME,COMPANY_NAME,SYSTEM,REQUEST,RESPONSE";
		$values = "'".$log['no_akaun']."','".$log['url_api']."','".$log['decodeToken']->user_name."','".$log['decodeToken']->company_name."','".$log['system']."',".$this->db->escape($_REQUEST)."";
		$sql = "declare
					str varchar2(32767);
				begin
					str	:= ".$this->db->escape($log['response']).";
					insert into GERAI.API_LOG ($set) values (".$values.",str);
				end;";
		$this->db->query($sql);
	}

	public function getNoPetak($harta,$kod){
		$this->db->select("NO_PETAK")
        		 ->from('GERAI.JENIS_SEWAAN')
				 ->where("HARTA",$harta)
				 ->where("KOD_JENIS_SEWAAN",$kod)
				 ->where("STATUS_SEWA IS NULL")
				 ->order_by("NO_PETAK",'ASC');
		$query = $this->db->get();
		if ($query->num_rows() > 0){
			$result = $query->result();
		}else{
			$result = 'No data';
		}
		return $result;
	}

	public function updateNoPetak($input){
		$mgs = false;
		$this->db->set('status_sewa', $input['status_sewa'])
				 ->set('no_akaun', $input['no_akaun'])
				 ->set('fail_rujukan', $input['fail_rujukan'])
				 ->set('nama_penyewa', $input['nama'])
				 ->set('sewa_seunit', $input['sewa_seunit'])
				 ->set('kp1', $input['kp'])
				 ->where('harta', $input['harta'])
				 ->where('kod_jenis_sewaan', $input['kod_jenis_sewaan'])
				 ->where('kod', $input['kod'])
				 ->where('no_petak', $input['no_petak'])
				 ->update('GERAI.JENIS_SEWAAN');
		if($this->db->affected_rows() == '1'){
			$mgs = true;
		}
		return $mgs;
	}

	public function updatePostPay($input){
		$mgs = false;
		
		if(!is_null($input['TARIKH_POST'])){
			$this->db->set('TARIKH_POST', "to_date('".$input['TARIKH_POST']."','DDMMYYYY HH24MISS')",FALSE)
						->where('NO_AKAUN', $input['NO_AKAUN'])
						->where('NO_RESIT', $input['NO_RESIT'])
						->where('SALURAN', $input['SALURAN'])					
						->update("GERAI.BAYARAN_TERKINI");
			if($this->db->affected_rows() == '1'){
				$mgs = true;
			}
		}
		return $mgs;
	}
}
