<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class mapping extends CI_Controller {
var  $connectors=array();
	function __construct()
	{
		parent::__construct();
		
		$this->load->library('tank_auth');
		$this->load->model('connector');
		$this->connectors=array();
		$this->connectors[0]=new oap_connector();
		$this->connectors[1]= new se_connector();
		
	}

	public function index()
	{
		$this->load->view('header');
		$this->load->view('content1');
		$this->load->view('footer');
		//connector list
		

	}
	function Load_all_connector()
	{
		$oap= new oap_connector();
		$se= new se_connector();
		$connectors=array($oap->name,$se->name);
		ob_start();
		echo form_open('mapping/save',array('name'=>'map','method'=>'post'));
		echo form_hidden('map_id', '0');
		echo "<table>";
		echo "<tr>";
		echo "<td>".form_dropdown('connector_from',$connectors)."</td>";
		echo "<td><span class='glyphicon glyphicon-arrow-right'></span></td>";
		echo "<td>".form_dropdown('connector_to',$connectors)."</td>";
		echo "<td>include</td>";
		echo "</tr>";
		
		foreach ($oap->key['users']['Contact Information'] as $key=>$oap_field)
		{
			echo "<tr>";
			echo "<td>".form_dropdown($oap->name.'_'.$key, $oap->key['users']['Contact Information'],$key)."</td>";
			echo "<td><span class='glyphicon glyphicon-arrow-right'></span></td>";
			echo "<td>".form_dropdown($se->name.'_'.$key, $se->key['users']) ."</td>";
			echo "<td>".form_checkbox('include_'.$key, '', TRUE)."</td>";
			echo "</tr>";
			
		}
		echo "</table>";
		echo form_submit('submit', 'Save Map');
		echo form_close();
		$data['map']=ob_get_clean();
		header('Content-Type: text/html; charset=utf-8');
		$this->load->view('header');
		$this->load->view('content1',$data);
		$this->load->view('footer');
		//print_r($se->key);
		//print_r($oap->key);
	}
	function save()
	{
		$this->load->model('connector_map');
		$map=new connector_map();
		print_r($this->input->post());
	}
	
	function step1()
	{
		if (!$this->tank_auth->is_logged_in()) {
			redirect('/auth/login/');
		}
		$data['user_id']	= $this->tank_auth->get_user_id();
		$data['username']	= $this->tank_auth->get_username();
		$data['role']		= $this->tank_auth->get_role();		
		
		$connectors_list=array();
		foreach ($this->connectors as $key=>$val)
		{	
			$connectors_list[]=$val->name;
		}
		$list=$this->connector->getAll(1, array('user'=>'desc'), array('user'=>$data['user_id'])); 
		$list=$list[0];
		
		ob_start();
		echo form_open('mapping/step2',array('name'=>'map','method'=>'post'));
		echo form_hidden('map_id', '0');
		
		echo "<table>";
		echo "<tr>";
			echo "<td>".form_dropdown('connector_from',$connectors_list,$list->api_from)."</td>";
			echo "<td><span class='glyphicon glyphicon-arrow-right'></span></td>";
			echo "<td>".form_dropdown('connector_to',$connectors_list,$list->api_to)."</td>";
		echo "</tr>";
		echo "</table>";
		echo form_submit('submit', 'Step 2');
		echo form_close();
		$data['map']=ob_get_clean();
		$data['step']="Select API conncetion pair";
		header('Content-Type: text/html; charset=utf-8');
		$this->load->view('header');
		$this->load->view('content1',$data);
		$this->load->view('footer');		
	}

	function step2()
	{		
		
		if (!$this->tank_auth->is_logged_in()) {
			redirect('/auth/login/');
		}
		$data['user_id']	= $this->tank_auth->get_user_id();
		$data['username']	= $this->tank_auth->get_username();
		$data['role']		= $this->tank_auth->get_role();
		
		$connector_from=$this->connectors[$this->input->post('connector_from')];//0
		$connector_to  =$this->connectors[$this->input->post('connector_to')];//1
		$to_fields     =$connector_to->Fields('user');
		$from_fields   =$connector_from->Fields('user');
		
		foreach ($from_fields as $key=>$field)
		{
			if($field['show']==true)
			{
				$from_drop[$key]=$field['field'];
			}
		}
		foreach ($to_fields as $key=>$field)
		{
			if($field['show']==true)
			{
				$to_drop[$key]=$field['field'];
			}
		}
		$list=$this->connector->getAll(1, array('user'=>'desc'), array('user'=>$data['user_id'])); 
		$list=$list[0];
		$include=json_decode($list->selection);
		$map=json_decode($list->map);
		//echo '<pre>';
		//print_r($map);
		ob_start();
		echo form_open('mapping/step3',array('name'=>'map','method'=>'post'));
		echo form_hidden('map_id', '0');
		echo form_hidden('connector_from', $this->input->post('connector_from'));
		echo form_hidden('connector_to', $this->input->post('connector_to'));
		echo "<table>";
		echo "<tr>";
			echo "<th>Include </th>";
			echo "<th>".$connector_to->name."</th>";
			echo "<th><span class='glyphicon glyphicon-transfer'></span></th>";
			echo "<th>".$connector_from->name."</th>";
		echo "</tr>";
		
		foreach ($to_drop as $key=>$to)
		{
			//echo "<pre>";
			//print_r($from_fields);exit;
				echo "<tr>";
				echo "<td>".form_checkbox('include[]', $key, in_array($key,$include))."</td>";
				echo "<td>".form_input(array('name'=>$connector_to->abr."[$key]",'value'=>$to,'readonly'=>true))."</td>";
				echo "<td><span class='glyphicon glyphicon-transfer'></span></td>";
				if(isset($map->{$key}->{$connector_from->abr}[0]))
				{
					$map__=$map->{$key}->{$connector_from->abr}[0];
				}else
				{
					$map__='';
				}
				echo "<td>".form_dropdown($connector_from->abr."[$key]", $from_drop,$map__) ."</td>";
				//echo form_hidden('connector_to__[]', $to_fields[$key]);
				echo "</tr>";	
		}
		echo "</table>";
		echo form_submit('submit', 'Step 3');
		echo form_close();
		
		$data['map']=ob_get_clean();
		$data['step']="Choose field maps";
		header('Content-Type: text/html; charset=utf-8');
		$this->load->view('header');
		$this->load->view('content1',$data);
		$this->load->view('footer');		
	}	
	function step3()
	{
		if (!$this->tank_auth->is_logged_in()) {
			redirect('/auth/login/');
		}
		$data['user_id']	= $this->tank_auth->get_user_id();
		$data['username']	= $this->tank_auth->get_username();
		$data['role']		= $this->tank_auth->get_role();		
		
		$connector_from=$this->connectors[$this->input->post('connector_from')];
		$connector_to  =$this->connectors[$this->input->post('connector_to')];
		$from_fields   =$connector_from->Fields('user');
		
		$from_drop =$this->input->post($connector_from->abr);
		$to_drop   =$this->input->post($connector_to->abr);
		
		ob_start();
		
		$set=array();
		foreach ($this->input->post('include') as $key)
		{	
			$pair=array(
					$connector_from->abr=>array($from_drop[$key],$from_fields[$from_drop[$key]]['field']),
					$connector_to->abr=>array($key,$to_drop[$key])
				);
			$set[$key]=$pair;
		}
		//$o=new class();
		$o->user     =$this->tank_auth->get_user_id();
		$o->api_from =$this->input->post('connector_from');
		$o->api_to   =$this->input->post('connector_to');
		$o->fields1  =json_encode($this->input->post($connector_from->abr));
		$o->fields2  =json_encode($this->input->post($connector_to->abr));
		$o->map      =json_encode($set);
		$o->selection=json_encode($this->input->post('include'));
		
		$this->connector->create($o);
		
		$data['map']=ob_get_clean();
		$data['step']="Saving configuration";
		header('Content-Type: text/html; charset=utf-8');
		
		$this->load->view('header');
		$this->load->view('content1',$data);
		$this->load->view('footer');		
	}
	

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */