<?php
require_once('templates.php');  
require_once('dashboard.php');
require_once(PLUGIN_ROOT_PATH.'bookingcontrol.php');
require_once('functions.php');
require_once('reports.php');

class dharmaAdmin   {
	
	/* a big desiding function */
	function doPostActions(){
		global $wpdb;
		$this->bookingControler = new bookingControl();
		$action = (isset($_POST['action'])?$_POST['action']:'');	
		switch($action){
			case 'newbooking':
				$wpdb->insert($wpdb->prefix.DATABASE_PREFIX.'tempbookings',array('data' => serialize($_POST)));
				
				$_POST['noNights'] = findNonights($_POST['checkin'],$_POST['checkout']);
				
				$this->bookingControler->setupData($_POST,$_POST['paymentdata']);
				$this->feedBack = $this->bookingControler->makeNewBooking(0,$wpdb->insert_id);
				break;
			case 'checkin':
				global $wpdb;
				if($_POST['clubcard'] == 'on' ){
					$totaldue = $_POST['discounted'];
					$clubcard  = true;
				}else{
					$totaldue = $_POST['nodiscount'];
					$clubcard  = false;
				}
				$wpdb->update( $wpdb->prefix.DATABASE_PREFIX.'bookings', array('checkedin' => 1),array( 'invoice' => $_POST['invoice']));
				$this->feedBack[] =$this->bookingControler->insertInvoice(array('invoice' => $_POST['invoice'],'payment' => $_POST['payment'],'totaldue' => $totaldue,'clubcard' => $clubcard ));	
				break;
			case 'update':
				$_POST['noNights'] = findNonights($_POST['checkin'],$_POST['checkout']);
				$this->bookingControler->setupData($_POST,array('invoice'=>$_POST['invoice'], 'payed' => $_POST["payment"]));
				$this->feedBack  = $this->finalpage->makeNewBooking($this->deleteBooking( $_POST['invoice']));
				break;
			case 'delete':
				$this->deleteBooking($_POST['invoice']);		
				$this->feedBack[] = array('success', 'booking deleted');
				break;
			case 'makepayment':
				$this->feedBack[] =$this->bookingControler->insertInvoice(array('invoice' => $_POST['invoice'],'payment' => $_POST['payment']));
				break;
		}
	}
	function deleteBooking($input){
		global $wpdb;
		$v = $wpdb->get_var($wpdb->prepare('SELECT checkedin FROM '.$wpdb->prefix.DATABASE_PREFIX.'bookings WHERE invoice = %d', $input) );
		$wpdb->query($wpdb->prepare( 'DELETE FROM '.$wpdb->prefix.DATABASE_PREFIX.'bookings WHERE invoice = %d', $input));
		return $v;
	}


//----------------------------------------------------------------------[a little bit javascript, css and light html]	
	function includeCSSnDivs(){//enquestyle
		?>
		<link rel="stylesheet" type="text/css" href="<?=PLUGIN_ROOT_URL?>admin/styles.css"/>
		<div id="blackout"></div>
		<div id="blankbox" class="hidden popupup-box"></div>
		<?php
	}
	function includeScripts(){

		/*
		wp_enqueue_script('loadmask',PLUGIN_ROOT_URL.'libs/jquery.loadmask.min.js',array('jquery'));
		wp_enqueue_script('dadminscript',plugins_url('scripts.js', __FILE__),array('jquery','jquery-ui-datepicker','jquery-ui-core'));
		wp_enqueue_script('jplotmini',  PLUGIN_ROOT_URL.'libs/jplot/jquery.jqplot.min.js',                      array('jquery'));
		wp_enqueue_script('jplotdate',  PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.dateAxisRenderer.js',        array('jplotmini'));
		wp_enqueue_script('jplottext',  PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.canvasTextRenderer.js',      array('jplotmini'));
		wp_enqueue_script('jplotcurs',  PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.cursor.js',                    array('jplotmini'));
		wp_enqueue_script('jplothi',    PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.highlighter.js',               array('jplotmini'));
		wp_enqueue_script('jplottick',  PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.canvasAxisTickRenderer.js',  array('jplotmini'));
		*/

	?>
		<link rel="stylesheet" type="text/css" href="<?=PLUGIN_ROOT_URL?>libs/css/jquery-ui-1.8.5.custom.css"/>
		<script type="text/javascript">var pluginUrl = '<?=PLUGIN_ROOT_URL ?>';</script>
		<?php
		
	}

//------------------------------------------------------------------------------------------[little html functions]	
	function makeMenu ($items, $default){
		?>	<ul id="reports-menu"><?php foreach($items as $anLi):
				$class= ($_GET['section'] == $anLi || (empty($_GET['section']) && $anLi ==$default) ? 'current' : '');?>	
				<li class="<?=$class?>"><a href="?page=<?=$_GET['page']?>&amp;section=<?=$anLi?>"><?=$anLi?></a></li>		
		<?php endforeach ?></ul><?php
	}

	function showFeedback (){
		if(isset($this->feedBack)){
			?><div id="feedback"><ul><?php foreach($this->feedBack as $feed): ?> 
				<li class="<?=$feed[0]?>"><?=$feed[1]?></li>
			<?php endforeach ?></ul></div><?php 
		}
	}

/*
----------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------

below is wordpess settings page and menues

----------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------
*/
   function Admin_Menus() {
		if (!function_exists('current_user_can') || !current_user_can('manage_options')) return;
		add_menu_page(__('dharma booking', PLUGIN_TRANS_NAMESPACE), __('Dharma Booking', PLUGIN_TRANS_NAMESPACE), 0,PLUGIN_TRANS_NAMESPACE, array('dharmaAdmin', 'dashboard'), PLUGIN_ROOT_URL.'img/icon-tiny.png');
		
		foreach(array('calendar','reports','templates','rentals') as $menuItem){
			add_submenu_page(PLUGIN_TRANS_NAMESPACE , __($menuItem,PLUGIN_TRANS_NAMESPACE), __( ucfirst($menuItem),PLUGIN_TRANS_NAMESPACE), 0,$menuItem, array('dharmaAdmin',$menuItem));
		}
		add_submenu_page(PLUGIN_TRANS_NAMESPACE , __('Settings',PLUGIN_TRANS_NAMESPACE), __('Settings',PLUGIN_TRANS_NAMESPACE), 'manage_options','settings_api_sample', array('dharmaAdmin','settingsPage'));
		add_options_page(PLUGIN_TRANS_NAMESPACE, __('Settings',PLUGIN_TRANS_NAMESPACE), __('Settings',PLUGIN_TRANS_NAMESPACE), 'settings_api_sample', array('dharmaAdmin','settings'));
	}
	function rentals() 	{ require_once('rentals.php'); }
   function calendar () { include_once('calendar.php'); }
	function reports() 	{ $report = new reports;}
	function dashboard (){ $checkin = new checkinDashboard();	}
   function templates()	{ 
		$templatePage = new templateEngine ();
		$templatePage->display($_GET['section']);
	}
	
	function Init() {
		register_setting( 'Dharma_Vars_Group', 'Dharma_Vars', array('dharmaAdmin', 'Validate'));

      //base varibles		
		add_settings_section( 'B_ID',   'Core Settings',            array('dharmaAdmin', 'overview'), 'D_Settings');
		add_settings_field('Booking_State', 'System state',         array('dharmaAdmin','bookingStatedropdown'), 'D_Settings','B_ID');
		add_settings_field('Thanks_ID',     'Final page id',        array('dharmaAdmin','final_page'),           'D_Settings','B_ID');
		add_settings_field('admin_email',   'Admin Email',          array('dharmaAdmin','text'),                 'D_Settings','B_ID', array('Dharma_Vars','adminEmail'));
		add_settings_field('reply_email',   'Reply to Email',       array('dharmaAdmin','text'),                 'D_Settings','B_ID', array('Dharma_Vars','replyEmail'));
		add_settings_field('timezone',      'Time Zone',            array('dharmaAdmin','timezonedropdown'),     'D_Settings','B_ID');
		add_settings_field('currancySymbol','Currancy Symbol',     array('dharmaAdmin','currancySymbolDropDown'),'D_Settings', 'B_ID');
		add_settings_field('discountCard',  'Discount Card',        array('dharmaAdmin','discountCarddropdown'), 'D_Settings','B_ID');
		add_settings_field('arivalTime',    'Arival Time Options',  array('dharmaAdmin','textarea'),'D_Settings', 'B_ID',					array('Dharma_Vars','arivalTime'));
	
		//calendar page settings 
  		add_settings_section('Ca_ID','Calender page options',array('dharmaAdmin','overview'),'D_Settings');
		add_settings_field('CDays_Ahead',   'Defualt days in the future the calender starts on',array('dharmaAdmin','number'), 'D_Settings', 'Ca_ID', array('Dharma_Vars','cDaysAhead'));
		add_settings_field('CnoNite',       'Defualt number of nights for calender to display',array('dharmaAdmin','number'),  'D_Settings', 'Ca_ID', array('Dharma_Vars','CnoNite'));
		add_settings_field('CnoDays',       'Number of days a user can select',array('dharmaAdmin','number'),  'D_Settings', 'Ca_ID', array('Dharma_Vars','CnoDays'));
		add_settings_field('usetimeout',  'Use update time out on calendar page',array('dharmaAdmin', 'checkbox'), 'D_Settings', 'Ca_ID',   array('Dharma_Vars','updateTimeoutOn'));
		add_settings_field('updateTime',       'time in minuits for calendar to start count down',array('dharmaAdmin','number'),  'D_Settings', 'Ca_ID', array('Dharma_Vars','updateTime'));
		add_settings_field('updateWarning',       'Time in seconds for warning to highlight',array('dharmaAdmin','number'),  'D_Settings', 'Ca_ID', array('Dharma_Vars','updateWarning'));
		add_settings_field('showpopout',  'Show Pop-up box\'es',array('dharmaAdmin', 'checkbox'), 'D_Settings', 'Ca_ID',   array('Dharma_Vars','showpopout'));
		add_settings_field('showreserved',  'show the drop down even if its not selectable',array('dharmaAdmin', 'checkbox'), 'D_Settings', 'Ca_ID',   array('Dharma_Vars','showreserved'));
		add_settings_field('popoutcss',           'CSS over-rides for the calender poput',       array('dharmaAdmin','textarea'),'D_Settings', 'Ca_ID',array('Dharma_Vars','calenderpopoutcss'));	
		//	add_settings_field('colour1',           'colour one',       array('dharmaAdmin','text'),'D_Settings', 'Ca_ID',array('Dharma_Vars','color1'));	

      //styles and warning
  		add_settings_section('C_ID','Styles & Warnings',array('dharmaAdmin','overview'),'D_Settings');
		add_settings_field('cssfile',       'CSS file',             array('dharmaAdmin','cssfiledropdown'),      'D_Settings', 'C_ID'               );
		add_settings_field('CSS',           'CSS over-rides',       array('dharmaAdmin','textarea'),'D_Settings', 'C_ID',array('Dharma_Vars','css'));	
		add_settings_field('Offline_text',  'Offline warning',      array('dharmaAdmin','textarea'),'D_Settings', 'C_ID',array('Dharma_Vars','OfflineText'));
		add_settings_field('Testing_text',  'site testing warning', array('dharmaAdmin','textarea'),'D_Settings', 'C_ID',array('Dharma_Vars','TestingText'));

		//payment options group
		add_settings_section('P_ID','Payment Gateways',array('dharmaAdmin','overview'),'D_Settings');
		add_settings_field('Payment_ID',            'GateWay',array('dharmaAdmin','Payment_Type'),'D_Settings','P_ID'                                                  );
		add_settings_field('Payment_Account',       'Account ID',array('dharmaAdmin','text'),'D_Settings','P_ID',               array('Dharma_Vars','paymentAccount'));
		add_settings_field('Payment_gatewayID',     'gateway ID',array('dharmaAdmin','text'),'D_Settings','P_ID',               array('Dharma_Vars','gatewayid'));
		add_settings_field('Payment_Currency',      'Currency Code',array('dharmaAdmin','text'),'D_Settings', 'P_ID',        array('Dharma_Vars','payment_currency_code'));
		add_settings_field('Payment_Deposit_Amount','Deposit Persent',array('dharmaAdmin', 'number'), 'D_Settings', 'P_ID',  array('Dharma_Vars','payment_depoist'));
		add_settings_field('Payment_Take_Deposit',  'Take Deposit',array('dharmaAdmin', 'checkbox'), 'D_Settings', 'P_ID',   array('Dharma_Vars','takeDeposit'));
		add_settings_field('Payment_Take_Full',     'Take Full Amount',array('dharmaAdmin','checkbox'), 'D_Settings', 'P_ID',array('Dharma_Vars','takeFull'));
		
      //sms gate way
      add_settings_section('S_ID',       'SMS Gateway', array('dharmaAdmin', 'overview'), 'D_Settings'                                      );
      add_settings_field('SMS_State',    'SMS State',array('dharmaAdmin', 'SMS_State'), 'D_Settings', 'S_ID'                                         );
      add_settings_field('SMS_ID',       'SMS GateWay',array('dharmaAdmin', 'SMS_Type'), 'D_Settings', 'S_ID'                                        );
      add_settings_field('SMS_Account',  'Account',array('dharmaAdmin', 'text'), 'D_Settings', 'S_ID',              array('Dharma_Vars','smsAccount'));
      add_settings_field('SMS_Password',  'Password',array('dharmaAdmin','password'),'D_Settings','S_ID',            array('Dharma_Vars','smsPassword'));
      add_settings_field('SMS_API',      'API key',array('dharmaAdmin', 'text'), 'D_Settings', 'S_ID',              array('Dharma_Vars','smsAPI'));
		add_settings_field('SMS_goclient',  'sms option',array('dharmaAdmin', 'checkbox'), 'D_Settings', 'S_ID',   array('Dharma_Vars','smsOption'));
      add_settings_field('SMS_Phone',    'Admin Phone number', array('dharmaAdmin', 'text'), 'D_Settings', 'S_ID',  array('Dharma_Vars','smsPhone'));
      add_settings_field('SMS_Hours',    'Send sms to admin if booking is made with less than x hours till posible checkin.',                          array('dharmaAdmin', 'number'), 'D_Settings', 'S_ID',array('Dharma_Vars','smsHours'));
  
	}
	
	/*the settings page display */
	function settingsPage(){
		$Vars = get_option('Dharma_Vars');
		wp_enqueue_script('jquery-ui-core' );
		wp_enqueue_script('jquery-ui-datepicker' );
		wp_enqueue_script('rfadminscript',plugins_url('scripts.js', __FILE__),array('jquery'));
		?>
		<style>
         form{padding: 10px;}
         textarea {height: 85px; width: 100%;}
         table{display:none;}
         .form-table{border: 1px dashed rgb(211, 211, 233);border-radius: 3px;margin-top: 0px;}
         #settingspage h3 {
            background-color:rgb(228, 228, 255);
            padding: 10px;cursor: 
            pointer;border-radius: 4px;
            border: 1px dashed rgb(78, 78, 114); 
            margin-bottom:0px;
      }
         #settingspage h2 { 
            margin: 10px;
         }
        
		</style>

      <div id="settingspage">
			<h2 class="alignright">System time; <?=date('r  e ',time())?></h2>
			<?php screen_icon("options-general"); ?> 
			<h1>Settings</h1>			

			<form action="options.php" method="post" >
				<?php settings_fields('Dharma_Vars_Group'); ?>
				<?php do_settings_sections('D_Settings'); ?>
				<p class="submit"> <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" /> </p>
			</form>
		</div> 
		<div id="setup-instructions">
		<img src="<?=PLUGIN_ROOT_URL?>img/icon-large.png" class="alignright"/>
      <h2>To install this plug in put these short codes where you want them.</h2>
		<strong>[calender-page]</strong> is the code for the calender booking page <br />
		<strong>[final-page]</strong> the last page for the booking proccess <br />
		</p>
      <p>Then fill out the <strong>"Core Settings"</strong> higher up on this page.</p>
		
		<strong>[checkin-page]</strong> (optional) page form front desk access to booking system  <br />

      <strong> all front end strings are internationalized</strong>
      <h2>Thanks for installing my plugin,</h2>
      I am still working on many aspects of it, all sugestions are welcome. 
		</div>
		<?php
	}
	function Validate($input) { return $input; }
	function overview() {
   }

    function text($in){
		$Vars=get_option($in[0]); 
		?><input id="smsAccount" name="<?=$in[0]?>[<?=$in[1]?>]" class="regular-text" value="<?=$Vars[$in[1]];?>" type="text"/><?php 
	}
    function number($in){
		$Vars=get_option($in[0]); 
		?><input id="smsAccount" name="<?=$in[0]?>[<?=$in[1]?>]" class="regular-text" value="<?=$Vars[$in[1]];?>" type="number"/><?php 
	}
    function password($in){
		$Vars=get_option($in[0]); 
		?><input id="smsAccount" name="<?=$in[0]?>[<?=$in[1]?>]" class="regular-text" value="<?=$Vars[$in[1]];?>" type="password"/><?php 
	}
   function textarea($in) { 
		$Vars = get_option($in[0]); 
		?><textarea name="<?=$in[0];?>[<?=$in[1];?>]"><?=$Vars[$in[1]]; ?></textarea> <?php 
	}
	function checkbox($in){
      $Vars = get_option($in[0]);
		if($Vars[$in[1]] == 'yes') $checked = 'checked="checked"';
      ?><input name="<?=$in[0];?>[<?=$in[1];?>]" type="checkbox" <?=$checked?> value="yes"/> <?php
	}

//-----------------------------------------------------------Drop downs
function timezonedropdown(){
	
	static $regions = array(
		'Africa' => DateTimeZone::AFRICA,		'America' => DateTimeZone::AMERICA,
		'Antarctica' => DateTimeZone::ANTARCTICA,		'Asia' => DateTimeZone::ASIA,
		'Atlantic' => DateTimeZone::ATLANTIC,		'Australia' => DateTimeZone::AUSTRALIA,
		'Europe' => DateTimeZone::EUROPE,		'Indian' => DateTimeZone::INDIAN,
		'Pacific' => DateTimeZone::PACIFIC
	);

	foreach ($regions as $name => $mask) {
		$tzlist[$name][] = DateTimeZone::listIdentifiers($mask);
	}
	$Vars = get_option('Dharma_Vars');
   ?><select name="Dharma_Vars[timezone]"><?php
   foreach($tzlist as $k => $v){
		echo '<option value="" ><b>-'.ucfirst($k).'</b></option>';
		foreach($v[0] as $kk => $vv){
			$data = explode('/',$vv);
			echo '<option value="'.$vv.'" '.selected($Vars['timezone'],$vv).'>&nbsp;&nbsp;&nbsp;'.ucfirst($data[1]).'</option>';
		}
	}
	?> </select><?php
}
function currancySymbolDropDown(){
	$Vars = get_option('Dharma_Vars');
	?><select name="Dharma_Vars[currancySymbol]"><?php
	foreach(array('none','&euro;','&yen;','&pound;','$','&curren;','R') as $card){
		echo '<option value="'.$card.'" '.$selected.'><big>'.$card.'</big></option>';
	}

	?> </select> <em>Active item is <?=$Vars['currancySymbol']//php won't compare the curanncy codes?????wtf! ?></em>
	<?php
}


function discountCarddropdown(){
	$Vars = get_option('Dharma_Vars');
	?><select name="Dharma_Vars[discountCard]"><?php
	foreach(array('none','bbh','yha') as $card){
			echo '<option value="'.$card.'" '.selected( $Vars['discountCard'] , $card ).'>'.ucfirst($card).'</option>';
	}
	?> </select><?php
}
function cssfiledropdown(){
      $Vars = get_option('Dharma_Vars');
      $cssfiles = scandir(PLUGIN_ROOT_PATH.'frontend/css/');//rahter scan for just *.css
      unset($cssfiles[0],$cssfiles[1]);
      ?><select name="Dharma_Vars[cssfile]"><?php
		foreach($cssfiles as $card){
         echo '<option value="'.$card.'" '.selected( $Vars['cssfile'] , $card ).'>'.ucfirst($card).'</option>';
		}
		?> </select><?php
}
	function bookingStatedropdown(){
      $Vars = get_option('Dharma_Vars');
      ?><select name="Dharma_Vars[bookingState]"><?php
		foreach(array('live','testing','down')as $card){
				echo '<option value="'.$card.'" '.selected( $Vars['bookingState'] , $card ).'>'.ucfirst($card).'</option>';
		}
		?> </select><?php
}

    function SMS_State() {
        $Vars = get_option('Dharma_Vars');
        ?>
        <select name="Dharma_Vars[smsState]">
            <option value="up" <?php selected( $Vars['smsState'], 'up' ); ?>>Up</option>
            <option value="down" <?php selected( $Vars['smsState'], 'down' ); ?>>Down</option>
        </select>
        <?php
    }    
    function SMS_Type() {
        $Vars = get_option('Dharma_Vars');
        ?>
        <select name="Dharma_Vars[smstype]">
            <option value="clickatell" <?php selected( $Vars['smstype'], 'clickatell' ); ?>>clickatell</option>
        </select>
        <?php
	}
 
	function final_page() {
		$Vars = get_option('Dharma_Vars');
		$allPages = get_pages( );
		?><select name="Dharma_Vars[thanksPage]"><?php
		foreach($allPages as $aPage){
			?><option value="<?=$aPage->ID?>" <?php selected( $Vars['thanksPage'], $aPage->ID ); ?>><?=$aPage->post_title?></option><?php
		}
		?></select><?php
	}

//payment selctions 
	function Payment_Type() {
		$Vars = get_option('Dharma_Vars');
		?><select name="Dharma_Vars[paymenttype]">
		<option value="none" <?php selected( $Vars['paymenttype'], 'none' ); ?>>none</option>
		<option value="worldpay" <?php selected( $Vars['paymenttype'], 'worldpay' ); ?>>worldPay</option>
		<option value="paystation" <?php selected( $Vars['paymenttype'], 'paystation' ); ?>>paystation.co.nz</option>
		</select>	
		<?php
	}

   function Base_state() {
        $Vars = get_option('Dharma_Vars');
        ?>
        <select name="Dharma_Vars[bookingState]">
            <option value="live" <?php selected( $Vars['bookingState'], 'live' ); ?>>Live</option>
            <option value="down" <?php selected( $Vars['bookingState'], 'down' ); ?>>Down</option>
            <option value="testing" <?php selected( $Vars['bookingState'], 'testing' ); ?>>Testing</option>
        </select>
        <?php
	}
}
?>
