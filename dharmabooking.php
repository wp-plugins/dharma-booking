<?php
/*
Plugin Name: Dharma booking
Plugin URI: http://earthling.za.org/
Description: A self contained accomadation booking system, with extras!
Version: 2.38.3
Author: Jamie MacDonlad
Author URI: http://earthling.za.org
*/
/*
This project is intended to be a modular booking system and hopefully will become easy to use, modify and add too. 

It is generally split into 2 parts, admin and front end with two current exceptions. 
bookingcontrol.php, the object that manages users, invoices and bookings. 
admin/dashboard.php, part of the admin interface that can also be accessed via short-code 
both parts are called via dharmabooking.php

current modular areas are 
admin/reports, different reports for the admin
fronted/gateways, different payment gateways
fronted/css, different style sheets
extra short-codes and widgets 
*/

define( 'PLUGIN_ROOT_PATH', plugin_dir_path(__FILE__) );
define( 'PLUGIN_ROOT_URL',plugins_url().'/dharma-booking/' );
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

function my_enqueue($hook) {
		wp_enqueue_script('loadmask',PLUGIN_ROOT_URL.'libs/jquery.loadmask.min.js',array('jquery'));
		wp_enqueue_script('dadminscript',PLUGIN_ROOT_URL.'admin/scripts.js',array('jquery','jquery-ui-datepicker','jquery-ui-core'));

		wp_enqueue_script('jplotmini',  PLUGIN_ROOT_URL.'libs/jplot/jquery.jqplot.min.js',                      array('jquery'));
		wp_enqueue_script('jplotdate',  PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.dateAxisRenderer.js',        array('jplotmini'));
		wp_enqueue_script('jplottext',  PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.canvasTextRenderer.js',      array('jplotmini'));
		wp_enqueue_script('jplotcurs',  PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.cursor.js',                    array('jplotmini'));
		wp_enqueue_script('jplothi',    PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.highlighter.js',               array('jplotmini'));
		wp_enqueue_script('jplottick',  PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.canvasAxisTickRenderer.js',  array('jplotmini'));

}
add_action( 'admin_enqueue_scripts', 'my_enqueue' );

//--------------------------------------------------------------[page shortcodes]
add_shortcode("final-page", "final_page");
function  final_page () { 
	includeFrontendCSS();
	if(empty($_POST['invoice'])){
		echo '<h1 class="errors">'.__('Please make a booking to user this page.',PLUGIN_TRANS_NAMESPACE).'</h1>';
		return ;
	}
	global $wpdb;
	$bookingData = unserialize($wpdb->get_var($wpdb->prepare('SELECT data FROM '.$wpdb->prefix.DATABASE_PREFIX.'tempbookings WHERE id = %d',$_POST['invoice'])));
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
global $dh_db_version;
$dh_db_version = '2.0';

register_activation_hook(__FILE__,DATABASE_PREFIX.'install');
function dh_install() {
  global $wpdb;
	global $dh_db_version;
	if(get_option("dharma_db_version") >= $dh_db_version) return ;
	
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
	add_option("dharma_db_version", $dh_db_version);
}
//------------------------------------------------------------------------------------------------[install default data]
register_activation_hook(__FILE__,DATABASE_PREFIX.'install_data');
function dh_install_data() {
	global $wpdb;
	global $dh_db_version;
	if(get_option("dharma_db_version") >= $dh_db_version) return ;
	$table_name = $wpdb->prefix . DATABASE_PREFIX.'templates';;

	$wpdb->insert( $table_name, array( 
		'id' => 1,
		'section' => 'email',
		'weight' => 2,
		'name' => 'body',
		'type' => 'body',
		'label' => 'Email message body',
		'data' => '<h2 >[fullname] has a booking from [time] on [nicestartdate] until [niceenddate].</h2><h2 >For a total of [nicepeople] and [nicenights].</h2><p >[roomtext]<big><strong>Total with discount $[discountdue], without: $[totaldue]</strong></big></p><p><big>your details with us are</big></p><p ><small>[email] [phone]</small></p><p><small>[comment]</small></p>',
		));

	$wpdb->insert( $table_name, array( 
		'id' => 2,
		'section' => 'email',
		'weight' => 0,
		'name' => 'subject',
		'type' => 'subject',
		'label' => 'Email subject',
		'data' => 'Reservation confirmation: [nicepeople] [nicenights] arriving [checkin] [time] ',
		));

	$wpdb->insert( $table_name, array( 
		'id' => 3,
		'section' => 'email',
		'weight' => 9,
		'name' => 'roomtext',
		'type' => 'shortcode',
		'label' => '[roomtext] shortcode',
		'data' => '<li>[roomname]  [nicepeople]</li>',
		));

	$wpdb->insert( $table_name, array( 
		'id' => 9,
		'section' => 'notifications',
		'weight' => 0,
		'name' => 'subject',
		'type' => 'subject',
		'label' => 'Email subject',
		'data' => '[DB] [nicepeople] for [nicenights] arriving at  [time] on [startdate]',
		));

	$wpdb->insert( $table_name, array( 
		'id' => 10,
		'section' => 'notifications',
		'weight' => 2,
		'name' => 'body',
		'type' => 'body',
		'label' => 'Message body',
		'data' => '<p ><big>[fullname]</big> - &lt;a mailto="[email]"?&gt; [email]&lt;/a&gt; - &lt;a tell=" [phone]"&gt;[phone]&lt;/a&gt;</p><p ><strong>[checkin]  [time] ~ </strong><strong>[<span >nicepeople]</span> for [nicenights]</strong></p><h3 >Total with <span > with discount $[discountdue], </span><strong>without: $[totaldue]</strong></h3><p>[roomtext]</p><p>[comment]</p>',
		));

	$wpdb->insert( $table_name, array( 
		'id' => 12,
		'section' => 'notifications',
		'weight' => 4,
		'name' => 'roomtext',
		'type' => 'shortcode',
		'label' => '',
		'data' => '[nicepeople] in [roomname].<br />',
		));

	$wpdb->insert( $table_name, array( 
		'id' => 13,
		'section' => 'sms',
		'weight' => 1,
		'name' => 'clientsms',
		'type' => 'subject',
		'label' => 'SMS sent to client',
		'data' => 'Reservation: [fullname] [nicepeople] [nicenights] arriving [checkin] [money]. [adminphone]',
		));

	$wpdb->insert( $table_name, array( 
		'id' => 13,
		'section' => 'sms',
		'weight' => 3,
		'name' => 'officesms',
		'type' => 'subject',
		'label' => 'SMS sent to Office phone',
		'data' => '[startdate] [time]:[fullname] for [nicepeople] for [nicenights].[phone]',
		));

	$wpdb->insert( $table_name, array( 
		'id' => 14,
		'section' => 'final page',
		'weight' => 1,
		'name' => 'body',
		'type' => 'body',
		'label' => 'web page body',
		'data' => '<h2 >[fullname] has a booking with us, from [time] on [nicestartdate] until [niceenddate].</h2><h2 >For a total of [nicepeople] and [nicenights].</h2><p ><big>For the following[roomtext]</big></p><h3 >Total with <span > with discount $[discountdue], </span><strong >without: $[totaldue]</strong></h3><h3 >Your contact details are <small >[email]  [phone]</small></h3><p><small>[comment]</small></p>',
		));

	$wpdb->insert( $table_name, array( 
		'id' => 15,
		'section' => 'final page',
		'weight' => 8,
		'name' => 'roomtext',
		'type' => 'shortcode',
		'label' => '',
		'data' => '<li>[roomname]  [nicepeople]</li>',
		));

	$wpdb->insert( $table_name, array( 
		'id' => 16,
		'section' => 'final page',
		'weight' => 8,
		'name' => 'textmessage',
		'type' => 'shortcode',
		'label' => '',
		'data' => 'a message was sent to your phone [phone]'
		));

	update_option("dharma_db_version", $dh_db_version);
}
?>
