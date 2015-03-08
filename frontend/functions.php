<?php

function includeFrontendCSS(){//enquestyle
	$basevars = get_option('Dharma_Vars');
	$pluginUrl = PLUGIN_ROOT_URL;
	//	wp_enqueue_style( 'style-name', get_stylesheet_uri() );
	wp_enqueue_style( 'style-core', $pluginUrl.'frontend/css/core.css' );
	wp_enqueue_style( 'style-theme', $pluginUrl.'frontend/css/themes/style1.css' );
	/*
	if($basevars['bstrap'] == 'yes'){
		wp_enqueue_style( 'style-norm', $pluginUrl.'libs/normalize.css' );
		wp_enqueue_style( 'style-boot', $pluginUrl.'libs/bootstrap/css/bootstrap.min.css' );
	}
	*/
	?><style type="text/css"><?=$basevars['css'];?></style><?php
}
/*
makes up varilbles and string varibles as well as enquey scripts and style sheets needed for jquery 
*/
function includeFrontendScripts($type =  '',$strings,$settings){
	$pluginUrl = PLUGIN_ROOT_URL;
	switch($type){
		case 'calender':
			$refresh = $pluginUrl.'frontend/ajax/Calender.php';
			$process = $pluginUrl.'frontend/ajax/Procces.php';
			$gatewayProcess = $pluginUrl.'frontend/ajax/gateways/proccess.php';
			break;
		default:
			break;
	}
	?>
	<link rel="stylesheet" type="text/css" href="<?=$pluginUrl?>libs/css/jquery-ui-1.8.5.custom.css"/>

	<script type="text/javascript">
		var refreshPage 	= '<?=$refresh ?>';
		var ajaxProccess	='<?=$process ?>';
		var gatewayProccess 	='<?=$gatewayProcess?>';
		var gatewayType 	='<?=$settings['paymenttype']?>';
		var timerBase = '<?=($settings['updateTime']*60)?>';;
		var timerWarning = '<?=$settings['updateWarning']?>';;
		var updateTimeoutOn = '<?=$settings['updateTimeoutOn']?>';;
	
		var noGuestsAlertString 		= '<?=$strings['noGuestsAlert']?>';
		var calenderLoadingString 	= '<?=$strings['calenderLoading']?>';
		var formSavedString 	= '<?=$strings['formSaved']?>';
		var guestString 							= '<?=$strings['Guest']?>';
		var guestsString 						= '<?=$strings['Guests']?>';
		var nightsString 						= '<?=$strings['Nights']?>';
		var nightString 							= '<?=$strings['Night']?>';
		var arivingString						= '<?=$strings['ariving']?>';
		var proccessingString 			= '<?=$strings['proccessing']?>';
		var requestFailString 				= '<?=$strings['requestFail']?>';
		var validemailString 				= '<?=$strings['validemail']?>';
		var validnameString 				= '<?=$strings['validname']?>';
		var validphoneString 				= '<?=$strings['validphone']?>';
	</script>
	<?php 
	
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-core' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'jquery-color' );
	wp_enqueue_script(	'loadmask',PLUGIN_ROOT_URL.'libs/jquery.loadmask.min.js',array('jquery'));
	wp_enqueue_script(	'calenderscript',plugins_url('js/calender.js', __FILE__),array('loadmask','jquery','jquery-ui-datepicker'));
}


/*
returns array containing detailed the details about a particular rental
*/
	function getRentalFullDetails () {
       global $wpdb;
		 $roomtypes = array();
		$sql = 'SELECT id, name, minimum, capacity, price,discount, discription FROM '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes  WHERE active = 1 ORDER by menuorder';
		$res = mysql_query('SELECT id, name, minimum, capacity, price,discount, discription FROM '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes  WHERE active = 1 ORDER by menuorder');
		while ($row = mysql_fetch_assoc($res)) {
			$roomtypes[intval($row['id'])] =  $row;
		}
		return $roomtypes;
	}
/*
	all final info you want to get from user, name email, ext with input type to be feed to function below
*/
function userInfoDetails (){
	$Vars = get_option('Dharma_Vars');
  $smsVars = $Vars ;

	$returned = array(
		'fullname' => array('label' => __('Name',PLUGIN_TRANS_NAMESPACE), 'type' => 'text', 'reg' => true),
		'phone' => array('label' =>  __('Phone',PLUGIN_TRANS_NAMESPACE), 'type' => 'text', 'reg' => true),
		'email' => array('label' =>  __('E-mail',PLUGIN_TRANS_NAMESPACE), 'type' => 'text', 'reg' => true),
		'time' => array('label' =>  __('Arival Time',PLUGIN_TRANS_NAMESPACE), 'type' => 'select', 'options' => explode(',',$Vars['arivalTime']) )
		);
		
	if($Vars['smsOption'] == 'up'){	
		$returned['textmessage'] = array('label' => __('send sms to your phone',PLUGIN_TRANS_NAMESPACE), 'type' => 'smsbox');
	}
	$returned['comment'] = array('label' => __('Adtional comments',PLUGIN_TRANS_NAMESPACE), 'type' => 'textbox');
	return $returned;
}

/*
	a rough way to create the all inputs tags
*/
function makeInputs($input){
	foreach ($input as $name => $data) {
		$value = $_POST[$name];
		echo '<div class="calendarInput">';
		echo '<label for="'.$name.'" class="'.$data['type'].' Type'.$data['type'].'"  >'.$data['label'].':'.($data['reg']?'*':'').'</label>';
		switch($data['type']){ 
			case'select':
				echo "<select name='$name'>";
				foreach ($data['options'] as $opt)  echo "<option value='$opt'>$opt</option>";
				echo '</select> ';
				break;
			case 'tick':
				echo "<input  type='checkbox' name='checkbox[$name]' value='".$data['label']."'>";
				break;
			case 'textarea':
				echo "<input  type='checkbox' id='$name' class='".$data['type']."'>
							<textarea class='hidden' id='$name-tb' name='$name'>$value</textarea>";
				break;
			case 'textbox':
				echo "<textarea  id='$name-tb' name='$name'>$value</textarea>";
				break;
			case 'smsbox':
				?>
				<input  type='checkbox' name='checkbox[<?=$name?>]' value="<?=$data['label']?>" 
					onchange="$('#sms-warningbox').slideToggle('slow');")>
				<div id="sms-warningbox" class="hidden">
				<?=__('Send booking comfirmation to your phone. <br />Be sure to enter your full <a target="_blank" href="http://en.wikipedia.org/wiki/List_of_country_calling_codes#Complete_listing">international number</a>, ie +6431234567. Not avalible in usa',PLUGIN_TRANS_NAMESPACE)?>
				</div>
				<?php
				break;
			case 'text':
			default:
				echo "<input type='text' id='$name' name='$name' value='$value' class='".($data['reg']?'required':'')."' />";
				break;
		}
		echo '</div>';
		echo '';
	}
}


/*
returns an array with any translated strings, array is mostly used for the javascript interface
*/
function getStrings () {
	return array( 
		 'interface' => array(
			'Guest' => __('Guest',PLUGIN_TRANS_NAMESPACE),
			'Guests' => __('Guests',PLUGIN_TRANS_NAMESPACE),
			'Select' => __('Select',PLUGIN_TRANS_NAMESPACE),
			'Night' => __('Night',PLUGIN_TRANS_NAMESPACE),
			'Nights' => __('Nights',PLUGIN_TRANS_NAMESPACE),
			'ariving' => __('ariving',PLUGIN_TRANS_NAMESPACE),
			'noGuestsAlert' => __('Please select at least one night',PLUGIN_TRANS_NAMESPACE) ,
			'calenderLoading' =>__('loading, Please wait...',PLUGIN_TRANS_NAMESPACE),
			'formSaved' =>__('Saved',PLUGIN_TRANS_NAMESPACE),
			'proccessing' => __('Proccesing, Please wait...',PLUGIN_TRANS_NAMESPACE),
			'validemail' => __('your email',PLUGIN_TRANS_NAMESPACE),
			'validname' => __('your full name',PLUGIN_TRANS_NAMESPACE),
			'validphone' => __('your phone number',PLUGIN_TRANS_NAMESPACE),			
			'requestFail' => __('Request failed, try again',PLUGIN_TRANS_NAMESPACE)
		),
		 'errorDescriptions'  =>	array(
			  'name' => __('You must enter your name',PLUGIN_TRANS_NAMESPACE),
			  'phone' => __('You must enter a valid phone number; only numbers, spaces and these symbols are allowed: ( ) # / * + - .',PLUGIN_TRANS_NAMESPACE),
			  'email' => __('You must enter a valid e-mail address, for example: donotusethis@example.com',PLUGIN_TRANS_NAMESPACE),
			  'emailconfirm' => __('Your e-mail address and the confirmation are not the same',PLUGIN_TRANS_NAMESPACE),
			  'checkin' => __('You must enter a valid arrival date, for example: 16 Jun 2011',PLUGIN_TRANS_NAMESPACE),
			  'checkout' => __('You must enter a valid departure date, for example: 21 Dec 2012',PLUGIN_TRANS_NAMESPACE),
			  'dateRange' => __('The departure date must be later than the arrival one!',PLUGIN_TRANS_NAMESPACE),
			  'noBedsSelected' => __('You must book at least one bed in one room!',PLUGIN_TRANS_NAMESPACE),
			  'availab' => __('The beds you selected are not available for these dates',PLUGIN_TRANS_NAMESPACE)
			)
		 
	);
				
}

