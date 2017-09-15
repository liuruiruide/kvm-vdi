<?php
/*
 * KVM-VDI
 * Tadas Ustinavi?ius
 * 2017-02-03
 * Vilnius, Lithuania.
 */
include ('functions/config.php');
require_once ('functions/functions.php');
if (! check_session ()) {
	header ( "Location: $serviceurl/?error=1" );
	exit ();
}
slash_vars ();


function save_graphics_info() {

	$type = remove_specialchars ( $_POST ['type'] );
	$hypervisor = remove_specialchars ( $_POST ['hypervisor'] );
	
	if (empty ( $type)) {
		echo "error";
	}
	if (check_empty ( $hypervisor )) {
		echo 'MISSING_ARGS';
		exit ();
	}
	
	$video_name='';
	$video_bus='';
	$video_slot='';
	$video_function='';
	$audio_function='';
	$video_vendor='';
	$video_device='';
	$audio_device='';
	
	
	$devices = $_POST ['devices'];
	$device_array = explode(',',$_POST ['devices']);
	write_log('devices: '.$devices);
	
	$ssh_ip_port = get_SQL_array ( "SELECT * FROM hypervisors WHERE id = " . $hypervisor);
	ssh_connect ( $ssh_ip_port [0] ['ip'] . ":" . $ssh_ip_port[0] ['port'] );
	
	
	foreach ( $device_array as &$device ) {
		write_log('device: '.$device);
		if(empty($device)){
			continue;
		}
		/*
		 * get device info
		 */
		$strBusSlot = substr($device, 0,strlen($device)-2);

		$info = explode ( "\n", ssh_command ( "sudo lspci -nn|grep ".$strBusSlot, true ) );
		foreach ( $info as &$value ) {
			if (empty ( $value )) {
				continue;
			}
			
			$info1 = substr($value, 0,7);
			$info2 = explode(":",substr($value, 8));
			//write_log($device.'----'.$info1);
			if(0==strcmp($device, $info1)){
				$video_bus = substr($info1, 0,2);
				$video_slot = substr($info1, 3,2);
				$video_function = substr($info1, 6,1);
				
				$video_vendor = substr($info2[1],strlen($info2[1])-4,4);
				$video_device = substr($info2[2],0,4);
				
				$video_name= explode ( ":", ssh_command ( "sudo lspci |grep ".$info1, true ) )[2];

			}else{
				write_log('get audio info: '.$info1);
				$audio_function= substr($info1, 6,1);
				$audio_device= substr($info2[2],0,4);
			}
		}
		$indb_graphics = get_SQL_array ( "SELECT * FROM graphics WHERE hypervisors = ".$hypervisor." and bus='".$video_bus."'"." and slot='".$video_slot."'"." and function='".$video_function."'");
		if(sizeof($indb_graphics)>0) {
			continue;
		}
		//add_SQL_line
		add_SQL_line( "INSERT INTO graphics (
				hypervisors,name
				,bus,slot,function,function_audio
				,vendor ,device  ,device_audio
				) VALUES (
				'$hypervisor','$video_name'
				,'$video_bus','$video_slot','$video_function','$audio_function'
				,'$video_vendor','$video_device','$audio_device'
				)"
				);
	}
	
	return "SUCCESS";
}

function get_graphincs_indb($id){
	$ssh_ip_port = get_SQL_array ( "SELECT * FROM hypervisors WHERE id = " . $id );
	//write_log('sizeof hypervisors:'.sizeof($ssh_ip_port));
	$graphincs= get_SQL_array ( "SELECT * FROM graphics WHERE hypervisors = ".$id);
	//write_log(sizeof($graphincs));
	
	$ret_array = array ();
	$x = 0;
	while ($x<sizeof($graphincs)) {
		$key = $graphincs[$x]['bus'].':'.$graphincs[$x]['slot'].'.'.$graphincs[$x]['function'];
		$value = $graphincs[$x]['name'];
		$ret_array[$key] = $value;
		++ $x;
	}
	
	//write_log(json_encode ( $ret_array ));
	return json_encode ( $ret_array );
}

function get_lspci_info($id) {
	$ssh_ip_port = get_SQL_array ( "SELECT * FROM hypervisors WHERE id = " . $id );	
	ssh_connect ( $ssh_ip_port [0] ['ip'] . ":" . $ssh_ip_port[0] ['port'] );
	$files = explode ( "\n", ssh_command ( "sudo lspci |grep VGA", true ) );
	$ret_array = array ();
	foreach ( $files as &$value ) {
		if (! empty ( $value )){
			$key = substr ( $value, 0, 7 );
			write_log('key:'.$key);
			$key_value = explode (":", $value )[2];
			write_log('value:'.$key_value);
			$ret_array [$key] = $key_value;
		}
	}
	

	$graphincs= get_SQL_array ( "SELECT * FROM graphics WHERE hypervisors = ".$id);
	$graphincs_array = array ();
	$x = 0;
	while ($x<sizeof($graphincs)) {
		$key = $graphincs[$x]['bus'].':'.$graphincs[$x]['slot'].'.'.$graphincs[$x]['function'];
		$value = $graphincs[$x]['name'];
		$graphincs_array[$key] = $value;
		++ $x;
	}
	//write_log('sizeof graphincs:'.sizeof($graphincs_array));

	$ret_array_new = array ();
	if (sizeof($graphincs_array) > 0){
		//write_log("---------------marge array");
		$ret_array_new = array_diff_key($ret_array,$graphincs_array);
	}else {
		$ret_array_new = $ret_array;
	}
	
	//

	//write_log(json_encode ( $ret_array_new));
	return json_encode ( $ret_array_new);
}

function get_lspci_info2($id) {
	$ssh_ip_port = get_SQL_array ( "SELECT * FROM hypervisors WHERE ip = " . $id );
	ssh_connect ( $ssh_ip_port [0] ['ip'] . ":" . $h_reply [0] ['port'] );
	$files = explode ( "\n", ssh_command ( "lspci -nn |grep 02:00", true ) );
	$ret_array = array ();
	foreach ( $files as &$value ) {
		if (! empty ( $value ))
			$key = substr ( $audio_bus, 0, 7 );
		$key_value = explode ( ':', $value ) [1];
		$ret_array [$key] = $key_value;
	}
	return json_encode ( $ret_array );
}


if (isset( $_POST['type'] ) || isset( $_GET['type'] )) {
	$type = '';
	$hypervisor = '';
	write_log(" get ".$_POST['type']);
	if(!empty($_POST['type']) && 0==strcmp($_POST['type'], "save")){
		echo save_graphics_info();

	}else if(!empty($_GET['type'])){
		$type = remove_specialchars ( $_GET['type'] );
		$hypervisor = remove_specialchars ( $_GET['hypervisor'] );
		$side = remove_specialchars ( $_GET['side'] );
	
		switch ($type) {
			case "lspci" :
				write_log($type.'|'.$hypervisor.'|'.$side);
				if (0==strcmp($side,"from")){
					echo get_lspci_info ($hypervisor);
				} else  {
					echo get_graphincs_indb($hypervisor);
				}
				break;
		}
	}
}
exit ();
?>
