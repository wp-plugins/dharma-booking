<?php
/*
Plugin Name: Dharma booking
Plugin URI: http://earthling.za.org/
Description: A self contained accomadation booking system, with a modulear disinge and front desk chekin page. Completly open GPL.  
Version: 2.1 beta
Author: Jamie MacDonlad
Author URI: http://earthling.za.org
*/

/*
This project is intended to be a modular booking system and hopefully will become easy to use, modify and add too. 

It is generally split into 2 parts, admin and front end with two current exceptions. 
bookingcontrol.php, the object that manages users, invoices and bookings. 
admin/dashboard.php, part of the admin interface that can also be accessed via short-code 
both parts are called via dharmabooking.php

code isn't very modular at the moment, though I hope for some feed back on how to improve this.
current modular areas are 
admin/reports, different reports for the admin
fronted/gateways, different payment gateways
fronted/css, different style sheets
extra short-codes and widgets 


It is under open GPL and I welcome any ideas.

I am afraid documentation is a little sparse in some areas, so far its been a solo project. So, questions welcome :)

please remember to internationalize any displayed strings 

*/


define( 'PLUGIN_ROOT_PATH', plugin_dir_path(__FILE__) );
define( 'PLUGIN_ROOT_URL',plugins_url().'/dharma/' );
define( 'PLUGIN_TRANS_NAMESPACE','dharma' );
define( 'DATABASE_PREFIX','dh_' );

include_once(PLUGIN_ROOT_PATH.'admin/dharmaAdmin.php');
require_once(PLUGIN_ROOT_PATH.'frontend/calender.php'); 
require_once(PLUGIN_ROOT_PATH.'bookingcontrol.php');
require_once(PLUGIN_ROOT_PATH.'admin/dashboard.php');
require_once(PLUGIN_ROOT_PATH.'admin/functions.php');

$Vars = get_option('Dharma_Vars');
date_default_timezone_set($Vars['timezone'] ); 

//-----------------------------------------------------------------------[admin pages]
add_action('admin_init', array('dharmaAdmin', 'Init'));
add_action('admin_menu', array('dharmaAdmin', 'Admin_Menus'));

//--------------------------------------------------------------[page shortcodes]
add_shortcode("final-page", "final_page");
function  final_page () { 
	includeFrontendCSS();
	if(empty($_POST['invoice'])){
		echo '<h1 class="errors">'.__('Please make a booking to user this page.',PLUGIN_TRANS_NAMESPACE).'</h1>';
		return ;
	}
	global $wpdb;
	$bookingData = unserialize($wpdb->get_var($wpdb->prepare('SELECT data FROM '.$wpdb->prefix.DATABASE_PREFIX.'tempbookings WHERE id = %s',$_POST['invoice'])));
	
	$bookingControler = new bookingControl();
	$bookingControler->setupData($bookingData, array('payment'=>$_POST['payment'],'invoice'=>$_POST['invoice'])	);
	if($bookingControler->siteVars['bookingState'] == 'testing') echo '<h1 class="debugclass">'.__('This sytem is in testing.',PLUGIN_TRANS_NAMESPACE).'</h1>';
	$bookingControler->testanddisplay();
}
add_shortcode("calender-page", "calender_page");
function calender_page () { 
	$calender = new matrixCalender;
	$calender->displayAll();
}
add_shortcode("checkin-page", "checkin_page");
function  checkin_page () {$checkin = new checkinDashboard();}


//---------------------------------------------------------------------------[localization]
function dh_init() {
	load_plugin_textdomain( PLUGIN_TRANS_NAMESPACE, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action('plugins_loaded', 'dh_init');


/*------------------------------------------------------------------------------------------------
--------------------------------------[installation hooks and functions]
------------------------------------------------------------------------------------------------*/
//--------------------------------------------------------------------------[table creation]
register_activation_hook(__FILE__,DATABASE_PREFIX.'install');
function dh_install() {
   global $wpdb;
	$dbversion = 1.7; //also replace in other function...
	if(get_option("dharma_db_version") >= $dbversion) return ;
	
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
	//bellow is hard to maintain
	$my_plugin_table = $wpdb->prefix . DATABASE_PREFIX.'bookings';
	if ( $wpdb->get_var( "show tables like '$my_plugin_table'" ) != $my_plugin_table ) {
		$sql = "
		CREATE TABLE IF NOT EXISTS `$my_plugin_table` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `idguest` int(10) unsigned NOT NULL,
		  `idroomtype` int(10) unsigned NOT NULL,
		  `beds` int(10) NOT NULL,
		  `checkin` date NOT NULL,
		  `checkout` date NOT NULL,
		  `numberofnights` smallint(5) unsigned NOT NULL,
		  `invoice` int(10) unsigned NOT NULL,
		  `bookingtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `checkedin` tinyint(4) NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `idguest` (`idguest`),
		  KEY `idroom` (`idroomtype`)
		)";
		dbDelta( $sql );
	}
	
		$my_plugin_table = $wpdb->prefix . DATABASE_PREFIX.'guests';
	if ( $wpdb->get_var( "show tables like '$my_plugin_table'" ) != $my_plugin_table ) {
			$sql = "CREATE TABLE IF NOT EXISTS `$my_plugin_table` (
						  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
						  `name` varchar(50) NOT NULL,
						  `phone` varchar(50) NOT NULL,
						  `email` varchar(50) NOT NULL,
						  `clubcard` varchar(1000) DEFAULT NULL,
						  PRIMARY KEY (`id`)
						) ";
		dbDelta( $sql );
	}
	
		$my_plugin_table = $wpdb->prefix . DATABASE_PREFIX.'invoices';
	if ( $wpdb->get_var( "show tables like '$my_plugin_table'" ) != $my_plugin_table ) {
		$sql = "CREATE TABLE IF NOT EXISTS `$my_plugin_table` (
				  `invoice` int(10) unsigned NOT NULL,
				  `clubcard` tinyint(1) NOT NULL,
				  `totaldue` float unsigned NOT NULL,
				  `payment` float unsigned NOT NULL,
				  `arivaltime` tinytext NOT NULL,
				  `updatetime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
				  `comment` mediumtext NOT NULL,
				  `paymentdata` longtext NOT NULL,
				  `bookingdata` text NOT NULL,
				  PRIMARY KEY (`invoice`)
				) ";
		dbDelta( $sql );
	}
	
		$my_plugin_table = $wpdb->prefix . DATABASE_PREFIX.'roomtypes';
	if ( $wpdb->get_var( "show tables like '$my_plugin_table'" ) != $my_plugin_table ) {
		$sql = "CREATE TABLE IF NOT EXISTS `$my_plugin_table` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `active` tinyint(1) NOT NULL DEFAULT '1',
		  `menuorder` int(10) unsigned NOT NULL,
		  `name` varchar(50) NOT NULL,
		  `minimum` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'beds per booking',
		  `capacity` int(10) unsigned NOT NULL,
		  `price` float NOT NULL,
		  `discount` float unsigned NOT NULL,
		  `discription` text NOT NULL,
		  PRIMARY KEY (`id`)
			) ";
		dbDelta( $sql );
	}
	
		
	$my_plugin_table = $wpdb->prefix . DATABASE_PREFIX.'tempbookings';
	if ( $wpdb->get_var( "show tables like '$my_plugin_table'" ) != $my_plugin_table ) {
	$sql = "CREATE TABLE IF NOT EXISTS `$my_plugin_table` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `data` text NOT NULL,
		  PRIMARY KEY (`id`)
		) ";
		dbDelta( $sql );
	}

	$my_plugin_table = $wpdb->prefix . DATABASE_PREFIX.'templates';
	if ( $wpdb->get_var( "show tables like '$my_plugin_table'" ) != $my_plugin_table ) {
		$sql = "CREATE TABLE IF NOT EXISTS `$my_plugin_table` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `section` tinytext NOT NULL,
		  `weight` tinyint(3) unsigned NOT NULL,
		  `name` tinytext NOT NULL,
		  `type` tinytext NOT NULL,
		  `label` text NOT NULL,
		  `data` mediumtext NOT NULL,
		  KEY `id` (`id`)
		) ";
		dbDelta( $sql );
	}
}
//------------------------------------------------------------------------------------------------[install default data]
register_activation_hook(__FILE__,DATABASE_PREFIX.'install_data');
function dh_install_data() {
	global $wpdb;
	$dbversion = 1.8; //also replace in other function...

	if(get_option("dharma_db_version") < $dbversion){
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$my_plugin_table = $wpdb->prefix . DATABASE_PREFIX.'templates';
    
		$sql = "INSERT INTO `$my_plugin_table` (`id`, `section`, `weight`, `name`, `type`, `label`, `data`) VALUES
(1, 'email', 2, 'body', 'body', 'Email message body', '<h2 >[fullname] has a booking from [time] on [nicestartdate] until [niceenddate].</h2>\r\n<h2 >For a total of [nicepeople] and [nicenights].</h2>\r\n<p >[roomtext]\r\n<big><strong>Total with <img class='alignnone size-full wp-image-378' alt='bbh' src='wp-content/uploads/2013/07/bbh.png' width='17' height='15' />$[discountdue], without: $[totaldue]</strong></big></p>\r\n<p><big>your details with us are</big></p>\r\n<p ><small>[email] [phone]</small></p>\r\n<p><small>[comment]</small></p>'),
(2, 'email', 0, 'subject', 'subject', 'Email subject', 'Reservation confirmation: [nicepeople] [nicenights] arriving [checkin] [time] '),
(3, 'email', 9, 'roomtext', 'shortcode', '[roomtext] shortcode', '<li>[roomname]  [nicepeople]</li>       '),
(9, 'notifications', 0, 'subject', 'subject', 'Email subject', '[DB] [nicepeople] for [nicenights] arriving at  [time] on [startdate]'),
(10, 'notifications', 2, 'body', 'body', 'Message body', '<p ><big>[fullname]</big> - &lt;a mailto='[email]'?&gt; [email]&lt;/a&gt; - &lt;a tell=' [phone]'&gt;[phone]&lt;/a&gt;</p>\r\n<p ><strong>[checkin]  [time] ~ </strong><strong>[<span >nicepeople]</span> for [nicenights]</strong></p>\r\n<h3 >Total with <span ><img class='alignnone size-full wp-image-378' alt='bbh' src='wp-content/uploads/2013/07/bbh.png' width='17' height='15' />$[discountdue], </span><strong>without: $[totaldue]</strong></h3>\r\n<p>[roomtext]</p>\r\n<p>[comment]</p>'),
(12, 'notifications', 4, 'roomtext', 'shortcode', '', '[nicepeople] in [roomname].<br />             '),
(13, 'sms', 1, 'clientsms', 'subject', 'SMS sent to client', 'Reservation: [fullname] [nicepeople] [nicenights] arriving [checkin] [money]. [adminphone]'),
(13, 'sms', 3, 'officesms', 'subject', 'SMS sent to Office phone', '[startdate] [time]:[fullname] for [nicepeople] for [nicenights].[phone]'),
(14, 'final page', 1, 'body', 'body', 'web page body', '<h2 >[fullname] has a booking with us, from [time] on [nicestartdate] until [niceenddate].</h2>\r\n<h2 >For a total of [nicepeople] and [nicenights].</h2>\r\n<p ><big>For the following\r\n[roomtext]\r\n</big></p>\r\n\r\n<h3 >Total with <span ><img class='alignnone size-full wp-image-378' alt='bbh' src='wp-content/uploads/2013/07/bbh.png' width='17' height='15' />$[discountdue], </span><strong >without: $[totaldue]</strong></h3>\r\n<h3 >Your contact details are <small >[email]  [phone]</small></h3>\r\n<p><small>[comment]</small></p>'),
(15, 'final page', 8, 'roomtext', 'shortcode', '', '<li>[roomname]  [nicepeople]</li>'),
(16, 'final page', 8, 'textmessage', 'shortcode', '', 'a message was sent to your phone [phone]')";
		dbDelta( $sql );

		$my_plugin_table = $wpdb->prefix . DATABASE_PREFIX.'templates';
		$sql = "INSERT INTO `$my_plugin_table` (`id`, `active`, `menuorder`, `name`, `minimum`, `capacity`, `price`, `discount`, `discription`) VALUES (1, 1, 1, 'upper bunglow', 2, 7, 30, 24, '<h2>Auskleiden weg brotkugeln getunchten dammerigen grundstuck flo gut ten</h2>\\r\\n\\r\\n<p>Ein gefallts hinunter stabelle vor schlafen neunzehn gekommen. Mi ward in he lang fiel ja habt ware mehr. Verschwand launischen und gab betrachtet angenommen erhaltenen bei. Wurden laufen solang hol ehe rothfu gut. Wo nachtun da gerbers flecken in er filzhut sagerei. Des herunter kindbett vor nirgends. Taghell wo gelernt ja schoner pa heimweh. Esse hand ans zart filz ist.</p>'),
(2, 1, 1, 'lower bunglow', 3, 9, 20, 18, '<h2>Auskleiden weg brotkugeln getunchten dammerigen grundstuck flo gut ten</h2>\\r\\n\\r\\n<p>Ein gefallts hinunter stabelle vor schlafen neunzehn gekommen. Mi ward in he lang fiel ja habt ware mehr. Verschwand launischen und gab betrachtet angenommen erhaltenen bei. Wurden laufen solang hol ehe rothfu gut. Wo nachtun da gerbers flecken in er filzhut sagerei. Des herunter kindbett vor nirgends. Taghell wo gelernt ja schoner pa heimweh. Esse hand ans zart filz ist.</p>'),
(3, 1, 4, 'farm house', 20, 99, 17, 15, '<h2>Auskleiden weg brotkugeln getunchten dammerigen grundstuck flo gut ten</h2>\\r\\n\\r\\n<p>Ein gefallts hinunter stabelle vor schlafen neunzehn gekommen. Mi ward in he lang fiel ja habt ware mehr. Verschwand launischen und gab betrachtet angenommen erhaltenen bei. Wurden laufen solang hol ehe rothfu gut. Wo nachtun da gerbers flecken in er filzhut sagerei. Des herunter kindbett vor nirgends. Taghell wo gelernt ja schoner pa heimweh. Esse hand ans zart filz ist.</p>'),
(4, 1, 4, 'blue room', 3, 7, 29, 25, '<h2>Auskleiden weg brotkugeln getunchten dammerigen grundstuck flo gut ten</h2>\\r\\n\\r\\n<p>Ein gefallts hinunter stabelle vor schlafen neunzehn gekommen. Mi ward in he lang fiel ja habt ware mehr. Verschwand launischen und gab betrachtet angenommen erhaltenen bei. Wurden laufen solang hol ehe rothfu gut. Wo nachtun da gerbers flecken in er filzhut sagerei. Des herunter kindbett vor nirgends. Taghell wo gelernt ja schoner pa heimweh. Esse hand ans zart filz ist.</p>'),
(5, 1, 3, 'red room', 2, 8, 29, 25, '<h2>Auskleiden weg brotkugeln getunchten dammerigen grundstuck flo gut ten</h2>\\r\\n\\r\\n<p>Ein gefallts hinunter stabelle vor schlafen neunzehn gekommen. Mi ward in he lang fiel ja habt ware mehr. Verschwand launischen und gab betrachtet angenommen erhaltenen bei. Wurden laufen solang hol ehe rothfu gut. Wo nachtun da gerbers flecken in er filzhut sagerei. Des herunter kindbett vor nirgends. Taghell wo gelernt ja schoner pa heimweh. Esse hand ans zart filz ist.</p>'),
(6, 1, 3, 'penthouse', 1, 6, 50, 45, '<h2>Auskleiden weg brotkugeln getunchten dammerigen grundstuck flo gut ten</h2>\\r\\n\\r\\n<p>Ein gefallts hinunter stabelle vor schlafen neunzehn gekommen. Mi ward in he lang fiel ja habt ware mehr. Verschwand launischen und gab betrachtet angenommen erhaltenen bei. Wurden laufen solang hol ehe rothfu gut. Wo nachtun da gerbers flecken in er filzhut sagerei. Des herunter kindbett vor nirgends. Taghell wo gelernt ja schoner pa heimweh. Esse hand ans zart filz ist.</p>');";
		dbDelta( $sql );

/*
why the fook does nither of these work ???!!!
		$sql = 'INSERT INTO `'.$my_plugin_table.'` 
(`option_name`, `autoload`, `option_value`) VALUES
		("Dharma_Vars","yes",
a:28:{s:12:"bookingState";s:7:"testing";s:10:"thanksPage";s:1:"5";s:10:"adminEmail";s:0:"";s:10:"replyEmail";s:0:"";s:8:"timezone";s:14:"Pacific/Wallis";s:12:"discountCard";s:3:"bbh";s:10:"arivalTime";s:17:"morning,afternoon";s:17:"calenderpopoutcss";s:48:"left: 174px;top: 20px;width: 385px;";s:10:"cDaysAhead";s:1:"1";s:7:"CnoNite";s:1:"8";s:7:"CnoDays";s:2:"15";s:7:"cssfile";s:9:"style.css";s:3:"css";s:0:"";s:11:"OfflineText";s:92:"<center><h1>---------------------------- Offline ----------------------------</h1> </center>";s:11:"TestingText";s:85:"<center><h1>---------------------------- Testing ----------------------</h1></center>";s:11:"paymenttype";s:4:"none";s:14:"paymentAccount";s:0:"";s:21:"payment_currency_code";s:3:"nzd";s:15:"payment_depoist";s:2:"14";s:11:"takeDeposit";s:3:"yes";s:8:"takeFull";s:3:"yes";s:8:"smsState";s:4:"down";s:7:"smstype";s:10:"clickatell";s:10:"smsAccount";s:5:"admin";s:11:"smsPassword";s:6:"password";s:6:"smsAPI";s:0:"";s:8:"smsPhone";s:0:"";s:8:"smsHours";s:0:"";}
)';
	dbDelta( $sql );//unsure if working or right way todo 

		$wpdb->insert($wpdb->prefix . 'options',
			array('option_name' => 'Dharma_Vars','autoload'=> 'yes', 'option_value' =>
'a:33:{s:12:"bookingState";s:4:"live";s:10:"thanksPage";s:1:"5";s:10:"adminEmail";s:0:"";s:10:"replyEmail";s:0:"";s:8:"timezone";s:13:"Pacific/Nauru";s:14:"currancySymbol";s:3:"€";s:12:"discountCard";s:3:"bbh";s:10:"arivalTime";s:45:"11am,12am,1pm,2pm,3pm,4pm,5pm,6pm,8pm,9pm10pm";s:10:"cDaysAhead";s:1:"1";s:7:"CnoNite";s:1:"8";s:7:"CnoDays";s:2:"15";s:15:"updateTimeoutOn";s:3:"yes";s:10:"updateTime";s:1:"5";s:13:"updateWarning";s:2:"42";s:17:"calenderpopoutcss";s:48:"   left:174px;top:20px;width:385px;";s:6:"color1";s:0:"";s:7:"cssfile";s:9:"style.css";s:3:"css";s:0:"";s:11:"OfflineText";s:92:"<center><h1>---------------------------- Offline ----------------------------</h1> </center>";s:11:"TestingText";s:85:"<center><h1>---------------------------- Testing ----------------------</h1></center>";s:11:"paymenttype";s:4:"none";s:14:"paymentAccount";s:0:"";s:21:"payment_currency_code";s:3:"nzd";s:15:"payment_depoist";s:2:"14";s:11:"takeDeposit";s:3:"yes";s:8:"takeFull";s:3:"yes";s:8:"smsState";s:4:"down";s:7:"smstype";s:10:"clickatell";s:10:"smsAccount";s:5:"user";s:11:"smsPassword";s:6:"password";s:6:"smsAPI";s:0:"";s:8:"smsPhone";s:0:"";s:8:"smsHours";s:0:"";}'
		));
*/
	}

	add_option("dharma_db_version", $dbversion);
	update_option("dharma_db_version", $dbversion);
}
?>
