<?php
/***********************
	Functions to work with the page
***********************/

//To convert .cer files into .pem (certificates)
function convert_into_pem_file($cert_filename)
{
	$certificateCAcer = "certs\\$cert_filename";
	$certificateCAcerContent = file_get_contents($certificateCAcer);
	// Convert .cer to .pem, we create a temp file here!
	$certificateCApemContent =  '-----BEGIN CERTIFICATE-----'.PHP_EOL.chunk_split(base64_encode($certificateCAcerContent), 64, PHP_EOL).'-----END CERTIFICATE-----'.PHP_EOL;
	$certificateCApem = 'certs\\temp_cert.pem';
	file_put_contents($certificateCApem, $certificateCApemContent); 
	// We acquire the params of the new cert (name, email, curp, dates of cert, etc.)
	$cert_details = return_my_cert_params('certs', 'temp_cert.pem');
	// We now move the newly created .pem file into pem_store directory with good taxpayer RFC
	$ts = date('YmdHis');
	$taxpayer_rfc = $cert_details['x500UniqueIdentifier'];
	$pem_new_name = $taxpayer_rfc.'_'.$ts.'.pem';
	rename('certs\\temp_cert.pem', "pem_store\\$pem_new_name");
	// And we also archive the .cer file in archived directory
	rename($certificateCAcer, str_replace('.pem', '.cer', "certs\\archived\\$pem_new_name"));
	return "new_file_name:".$pem_new_name."<br>";
}

//To convert filesize into human readable format
function humanFileSize($size,$unit="") 
{
	if( (!$unit && $size >= 1<<30) || $unit == "GB")
	  return number_format($size/(1<<30),2)."GB";
	if( (!$unit && $size >= 1<<20) || $unit == "MB")
	  return number_format($size/(1<<20),2)."MB";
	if( (!$unit && $size >= 1<<10) || $unit == "KB")
	  return number_format($size/(1<<10),2)."KB";
	return number_format($size)." bytes";
}

//function to calculate the number of days between to dates
function number_of_days($today, $other_date)
{
	$datediff = strtotime($today) - strtotime($other_date);
	return round($datediff / (60 * 60 * 24));
}

//function to paint cell with expiration status inside table
function my_expiration_status_cell($days)
{
	if ($days < 0)
	{
		if($days > -60)
		{
			// 60 days mark, status is warning (yellow cell)
			$status = '<td class="table-warning">&#x26A0; '.abs($days).' días</td>';
		}
		else
		{
			// status is ok (green cell)
			$status = '<td class="table-success">OK &#x2705;</td>';
		}
		
	}
	else
	{
		// status is critical (red cell)
		$status = '<td class="table-danger">&#x274C; Expirada</td>';
	}
	return $status;
}

//function to convert subject name into array with values
function parse_my_subject_name_cert_data($valid_From, $valid_To, $txt_sn)
{
	$keyed_array = array();
	if(substr($txt_sn, 0, 1) == '/') //to check if first letter is an slash "/"
	{
		$txt_sn = substr($txt_sn, 1); //we remove the first slash!
	}
	$data_array = explode('/', $txt_sn);
	$l_sn = count($data_array);
	for ($i = 0; $i < $l_sn; $i++)
	{
		$tmp_array = explode('=', $data_array[$i]);
		$keyed_array[$tmp_array[0]] = $tmp_array[1];
	}
	$keyed_array['valid_from'] = $valid_From ;
	$keyed_array['valid_to'] = $valid_To ;
	// We calculate the days before expiration
	$today = date('Y-m-d H:i:s');
	$keyed_array['expiration'] = number_of_days($today, $valid_To);
	return $keyed_array;
}

//function to read cert given the name and return values
function return_my_cert_params($path, $file_name)
{
	//$file = @file_get_contents('D:\xampp\htdocs\fiel_sat\pem_store\\'.$file_name);
	$file = @file_get_contents("$path\\$file_name");
	$parsed_cert = openssl_x509_parse($file);
	$valid_From = date_create_from_format('ymdHise', $parsed_cert['validFrom'])->format('Y-m-d H:i:s');
	$valid_To = date_create_from_format('ymdHise', $parsed_cert['validTo'])->format('Y-m-d H:i:s');
	if(isset($parsed_cert['extensions']['subjectAltName']))
	{
		$subject_name = $parsed_cert['extensions']['subjectAltName'];
	}
	else
	{
		//$subject_name = 'None';
		$subject_name = $parsed_cert['name'];
	}
	$pretty_subject_name = parse_my_subject_name_cert_data($valid_From, $valid_To, $subject_name);
	return $pretty_subject_name;
}

//function to read pem_store directory an build inventory of certs to display
function create_my_cert_inventory($directory)
{
	$status_counters = array(0, 0, 0, 0);
	$cert_store_nms = array();
	$inventory = glob($directory."/*.pem");
	foreach ($inventory as $filename) 
	{
		$base_name_file = basename($filename);
		$cert_params_tmp =  return_my_cert_params($directory, $base_name_file);
		// We validate status of the cert and we add into counters
		if ($cert_params_tmp['expiration'] < 0)
		{
			if($cert_params_tmp['expiration'] > -60)
			{
				// 60 days mark, status is warning (yellow cell)
				$status_counters[2]++;
			}
			else
			{
				$status_counters[1]++;
			}
			
		}
		else
		{
			$status_counters[3]++;
		}
		$cert_store_nms[] = [$base_name_file, humanFileSize(filesize($filename)), $cert_params_tmp];
	}
	//We count how many certs we have on the pem_store directory
	$filecount = count($cert_store_nms);
	$status_counters[0] = $filecount;
	return array($status_counters, $cert_store_nms);
	//return array("Hay $filecount e.firmas en el directorio!", $cert_store_nms);
}

//function to read certs directory and know number of certificates to process
function read_my_cert_ingress()
{
	$cert_store_nms = array();
	$inventory = glob("certs/*.cer");
	foreach ($inventory as $filename) 
	{
		$base_name_file = basename($filename);
		$cert_store_nms[] = [$base_name_file, humanFileSize(filesize($filename))];
	}
	//first we count how many certs we have on the pem_store directory
	$filecount = count($cert_store_nms);
	return array("We have $filecount certificates to be processed!", $cert_store_nms);
}

//function to perform ingress of certs and conversion into .pem
function work_on_certs($cert_array)
{
	$total_certs = count($cert_array);
	$salida = '';
	if($total_certs > 0)
	{
		
		for($e = 0; $e < $total_certs; $e++)
		{
			$salida .= convert_into_pem_file($cert_array[$e][0]);
		}
	}
	return $salida;
}


//function to build navbar
function create_my_navbar($ad_user)
{
	$div = 
'			<!--<p class="text-white my-2 mx-1">Logged as: <b>'.$ad_user.'</b></p>
			<button class="btn btn-danger mx-2" id="logoutBTN">Logout!<i class="fas fa-sign-out-alt ms-2"></i></button>-->';
	
	$nav = 
'	<span class="navbar-brand text-white">
		<img src="img/conta.png" width="35" class="d-inline-block align-top me-2" alt="Logo"><b>e.firma SAT</b>
	</span>
	<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarScroll" aria-controls="navbarScroll" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>
	<div class="collapse navbar-collapse" id="navbarScroll">
		<ul class="navbar-nav me-auto my-2 my-lg-0 navbar-nav-scroll" style="--bs-scroll-height: 100px;">
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle text-white" href="#" id="navbarScrollingDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Select</a>
				<ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">
					<li><a class="dropdown-item" href="../fiel_sat/">Index</a></li>
				</ul>
			</li>
		</ul>
		<div class="d-flex" id="my_data_nav">
			'.$div.'
		</div>
	</div>'."\n";
	return $nav;
}

//function to display "status dashboard" with cards
function my_pem_status_dashboard($inventory, $counters)
{
	$html = 
'<div class="card-group w-50 my-4">
	<div class="card mb-3">
		<div class="card-header text-bg-primary"><h5><b>Total e.firmas</b></h5></div>
		<div class="card-body">
  			<h2 class="card-text">'.$counters[0].'</h2>
		</div>
	</div>
	<div class="card mb-3">
		<div class="card-header text-bg-success"><h5><b>Vigentes</b></h5></div>
		<div class="card-body">
			<h2 class="card-text">'.$counters[1].'</h2>
		</div>
	</div>
	<div class="card mb-3">
		<div class="card-header text-bg-warning"><h5><b>Por expirar</b></h5></div>
		<div class="card-body">
			<h2 class="card-text">'.$counters[2].'</h2>
		</div>
	</div>
	<div class="card mb-3">
		<div class="card-header text-bg-danger"><h5><b>Expiradas</b></h5></div>
		<div class="card-body">
			<h2 class="card-text">'.$counters[3].'</h2>
		</div>
	</div>
</div>'."\n";
	return $html;
}

/*********
	Manage AJAX Calls!
*********/

//to return navbar content
if (isset($_POST["go_navbar"])) 
{
	//$who = $_SESSION['userAD'];
	$who = 'admin';
	$nav_content = create_my_navbar($who);
	echo $nav_content;
}

//To display main options
if (isset($_POST["stamp_action_btn"])) 
{
	$html = 
'	<div class="btn-group" role="group" aria-label="Basic example">
		<button type="button" class="btn btn-outline-dark" data-id="action_panel_btn" data-id1="inventory"><i class="fas fa-warehouse me-2"></i>Inventario</button>
		<button type="button" class="btn btn-outline-dark" data-id="action_panel_btn" data-id1="ingress"><i class="fas fa-folder-open me-2"></i>Agregar</button>
 	</div>';
	echo $html;
}


//To display pem_store content
if (isset($_POST["list_inventory"])) 
{
	$inventory = create_my_cert_inventory('pem_store');
	$efirma_data = $inventory[1];
	$dashboard = my_pem_status_dashboard($efirma_data, $inventory[0]);
	$html = 
'<center>
	<h3 class="my-3">Listado de e.firmas:</h3>
	'.$dashboard.'
	<div class="d-inline-flex align-items-center my-1 w-25">
		<i class="fa fa-search me-2" aria-hidden="true"></i><input id="myInput" type="text" placeholder="Search.." class="form-control mx-2">
	</div>
	<!--<h6 class="my-3 fst-italic fw-lighter">'.$inventory[0][0].'</h6>-->
	<table class="table table-sm table-striped table-hover w-75 my-4">
		<thead class="table-dark"><tr><th>RFC</th><th>Nombre</th><th>Email</th><th>Status</th><th>Expiración</th><th>Detalles</th></tr></thead>
		<tbody id="myTable">'."\n";
	
	for($b = 0; $b < count($efirma_data); $b++)
	{
		$details_btn = '<button type="button" class="btn btn-info btn-sm" data-id="details_btn" data-id1="'.$efirma_data[$b][0].'"><i class="fas fa-info"></i></button>';
		$efirma_status = my_expiration_status_cell($efirma_data[$b][2]['expiration']);
		$html .= '			<tr><td>'.$efirma_data[$b][2]['x500UniqueIdentifier'].'</td><td>'.$efirma_data[$b][2]['name'].'</td><td>'.$efirma_data[$b][2]['emailAddress'].'</td>'.$efirma_status.'<td>'.$efirma_data[$b][2]['valid_to'].'</td><td>'.$details_btn .'</td></tr>'."\n";
	}
	$html .= 
'		</tbody>
	</table><br>
</center>'."\n";
	//$html .=  '<pre>'.print_r($inventory, true).'</pre><br>';
	echo $html;
}

//To display cert details in modal
if (isset($_POST["cert_details_modal"]) && isset($_POST["details_ts"])) 
{
	$ts = $_POST["details_ts"];
	$cert_name = $_POST["cert_details_modal"];
	$details = return_my_cert_params('pem_store', $cert_name);
	$lista_html = '<ul>'."\n";
	foreach ($details as $key => $value) 
	{
		$lista_html .= '  <li><b>'.$key.'</b>: '.$value.'</li>'."\n";
	}
	//echo '<pre>'.print_r($details, true).'</pre>'. $lista_html;
	$lista_html .= '</ul>'."\n";
	echo $lista_html;
}

//To launch conversion over certs directory
if (isset($_POST["cert_conv_start"])) 
{
	$out = '';
	$conversion_array = read_my_cert_ingress();
	$out .= '<pre>'.$conversion_array[0].'</pre><br>';
	echo $out;
}

//To launch conversion over certs directory
if (isset($_POST["cert_conversion"])) 
{
	$out = '';
	$conversion_array = read_my_cert_ingress();
	$out .= '<pre>'.$conversion_array[0].'</pre><br>';

	$converted_pems = work_on_certs($conversion_array[1]);
	$out .= '<pre>'.$converted_pems.'</pre><br>';
	echo $out;
}




/*
----------------- Old functions --------------------------

//send alert mail for expired pwd 
function sendCriticalMail($apli, $server, $usage, $env, $days, $SN, $tool_mail)
{
	//$to      = 'eduardo.medel@faurecia.com';
	$to      = 'DL-IT-SystemOperating-EITApplications@faurecia.com';
	$subject = '[Automatic Reminder] Certificate for '.$apli.' application is near to expire!';
	$headers = 'From: '.$tool_mail. "\r\n" .'MIME-Version: 1.0'."\r\n".'Content-Type: text/html; charset=UTF-8'."\r\n";
	$message = '<h2><font face="Century Gothic" color="#323232">Hello team,</font></h2><p><font face="Century Gothic" color="#323232">Certificate for <b>'.$apli.' - '.$env.'</b> is about to expire on: <b>'.$days.' days</b>, certificate is installed on: <b>'.$server.'</b> and it\'s used for: <b>'.$usage.' access</b>. SubjectName used on cert is: <b>'.$SN.'</b><br><br><i>Regards,<br>EIT Team</i></font></p><br><p><font face="Century Gothic" color="#323232" style="font-size:13px"><b><i>This is an automatic mail. Please do not reply to it.</i></b></p>';
	$out = 'nok_mail';
	if(@mail($to, $subject, $message, $headers))
	{
		$out = 'ok_mail';
	}
	return $out;
}

//function to build an array with all certs entries on database
function prepare_my_keystore_array($today, $link)
{
	$store = array(); 
	$sql = "SELECT cert_appli, cert_name, cert_server, cert_usage, cert_env, id FROM digital_d.cert_store WHERE status_record = 1 ORDER BY id ASC;";
	$result = mysqli_query($link, $sql); 
	$rows = mysqli_num_rows($result);
	if($rows > 0)
	{
		while($row = mysqli_fetch_array($result)) 
		{			
			$info_cert = return_my_cert_params($row["cert_name"]);
			$store[] = array($row["cert_appli"], $row["cert_server"], $row["cert_env"], t_int_to_usag($row["cert_usage"]), $info_cert[0], $info_cert[1], number_of_days($info_cert[1],$today), $info_cert[2], $row["id"]);
		}
	}	
	return $store;
}

//Function to update certificate record on keystore database
function disable_cert_status($id, $link)
{
	$salida = 'NOK';
	$sql = "UPDATE digital_d.cert_store SET status_record = 0 WHERE id = $id;";
	if(mysqli_query($link, $sql))  
	{  
		$salida =  'OK';
	}	
	return $salida;
}

//function to manage alerting|
function manage_my_alerting($data, $link, $tool_mail)
{
	if($data[6] <= 45)
	{
		//let's alert that expiration is coming
		$alert_mail = sendCriticalMail($data[0], $data[1], $data[3], $data[2], $data[6], $data[7], $tool_mail);
		$action = 'Alert:'.$alert_mail;
		if($data[6] <= 0)
		{
			$upd_res = disable_cert_status($data[8], $link);
			$action .= '; Disable:'.$upd_res;
		}		
	}
	else
	{
		//nothing to do!
		$action = 'N/A';
	}
	return $action;
}

include '../automation/params/global.php';
$today = date('Y-m-d H:i:s');
$link = new mysqli($db_server , $db_user, $db_pwd, $db_name);
$my_keys = prepare_my_keystore_array($today, $link);
$largo = count($my_keys );
$log_out = '<html><head><style>body {font-family: "Century Gothic", Arial, Helvetica, sans-serif;} table, td, th {  border: 1px solid black;}  table { width: 100%; border-collapse: collapse;}</style></head><body>';
$log_out .= '<h3>Number of certs in keystore: '.$largo.'</h3>';
$log_out .= '<table><tr><th>Application</th><th>Server</th><th>Env</th><th>Usage</th><th>Start</th><th>Expiration</th><th>Days</th><th>Action</th></tr>';
for ($k = 0; $k < $largo; $k++)
{
	$action = manage_my_alerting($my_keys[$k], $link, $eit_dash_tool_mail);
	$log_out .= '	</tr><td>'.$my_keys[$k][0].'</td><td>'.$my_keys[$k][1].'</td><td>'.$my_keys[$k][2].'</td><td>'.$my_keys[$k][3].'</td><td>'.$my_keys[$k][4].'</td><td>'.$my_keys[$k][5].'</td><td>'.$my_keys[$k][6].'</td><td>'.$action.'</td></tr>';
}
$log_out .= '</table></body></html>';
$link->close();
$mail_check = sendLogByMail($log_out, $today, $eit_dash_tool_mail);
echo $log_out.'<br>Mail: '.$mail_check;
echo $log_out;
*/

?>


