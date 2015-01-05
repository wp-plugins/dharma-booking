<?php 
/*
creates and updates guests, invoices, bookings 

*/
require_once('frontend/functions.php');

class bookingControl {

	function setupData($data, $paymentdata){
		$this->invoice = $paymentdata['invoice'];
		$this->payment = $paymentdata['payed'];
		$data['startDate'] =  (empty($data['startDate'])?date('Y-m-d', strtotime($data['checkin'])):$data['startDate']);
		$this->data = $data;		
		$this->siteVars = get_option('Dharma_Vars');		
		
		$totals = $this->getTotals();
		$this->totaldue = $totals[0];
		$this->discountdue = $totals[1];
		$this->balence = $this->totaldue - $this->payment;
		if (empty($this->data['rooms'])) {
			$this->feedback[] = array ('fail',__('pease select at least one room',PLUGIN_TRANS_NAMESPACE));
			return false;
		}
		foreach($this->data['rooms'] as $k => $v) $this->noPoeple += $v;
	}

	/* Test data then display the thanks page */
	function testanddisplay(){	
		global $wpdb;		
		$this->feedback = array();
		//things that we don't allow
		if(!$_POST['invoice']){	$this->feedback[] = array('fail',__('Please make a booking to use this page.',PLUGIN_TRANS_NAMESPACE));}
		if(!filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)){$this->feedback[] = array('fail',__('Please enter a valid email.',PLUGIN_TRANS_NAMESPACE));}
		if(empty($this->data['fullname'])){$this->feedback[] =array('fail',__('Please enter a name.',PLUGIN_TRANS_NAMESPACE));}	
		$sql = $wpdb->prepare('SELECT id FROM '.$wpdb->prefix.DATABASE_PREFIX.'bookings WHERE invoice = %s',$this->invoice);
		if(!is_null($wpdb->get_var($sql))){$this->feedback[] = array('fail',sprintf(__('%s your booking as been recorded, please don\'t refresh the page.',PLUGIN_TRANS_NAMESPACE),htmlspecialchars($this->data['fullname'])));}
		
		if(count($this->feedback) > 0){
			$this->showFeedback();
			return fail;
		}
		
		$this->makeNewBooking(0,$_POST['invoice']);		
		$this->doComunications();
		$this->showFeedback();
		$this->displayPage();
	}
	/* display the final page */
	private function displayPage(){		
		global $wpdb;
		$this->createReplacements('final page');
		$body= $wpdb->get_var($wpdb->prepare( 'SELECT data FROM '.$wpdb->prefix.DATABASE_PREFIX.'templates WHERE `section` LIKE %s AND name LIKE %s','final page','body') );
		echo stripslashes(str_replace(array_keys($this->replacements), array_values($this->replacements), $body));
	}
	
	/*pull together the data and create a new booking, invoce and or guest*/
	function makeNewBooking ($checkedin = 0, $tempid = false ) {
		$this->insertInvoice(array('invoice' => $this->invoice,'totaldue' => $this->totaldue,'payment' => $this->payment,'comment' => htmlspecialchars($this->data['comment'])), $tempid);
		$guestid = $this->getGuestId();
		$this->feedback[] = $this->insertBookings($guestid,$checkedin);
	}

	/* return current guest id or create and return guest id*/
	function getGuestId () {
		global $wpdb;		
		$guestDatafields= array('phone' => $this->data['phone'],'email' => $this->data['email'],'phone' => $this->data['phone']	,'name' => $this->data['fullname']);
		$guestid =  $wpdb->get_var( $wpdb->prepare('SELECT id FROM '.$wpdb->prefix.DATABASE_PREFIX.'guests WHERE email = %s AND name = %s',$this->data['email'],$this->data['fullname']));
		
		if(!empty($guestid)) {
			$wpdb->update( $wpdb->prefix.DATABASE_PREFIX.'guests', $guestDatafields,	array( 'id' => $guestid));
			$this->feedback[] = array('success', __('Guest has been updated',PLUGIN_TRANS_NAMESPACE));
			return $guestid;
		}
      $sql = $wpdb->insert( $wpdb->prefix.DATABASE_PREFIX.'guests',$guestDatafields) ;
		$this->feedback[] = array('success', __('Guest has been recorded',PLUGIN_TRANS_NAMESPACE));
		return $wpdb->insert_id;
	}
	
	/*insert this booking into the database*/
	function insertBookings ($guestid,$checkedin) {

		global $wpdb;
		if($id = $wpdb->get_var($wpdb->prepare('SELECT id FROM '.$wpdb->prefix.DATABASE_PREFIX.'bookings WHERE invoice = %s ',$this->invoice))){
			return array('fail',__('This booking has already been made',PLUGIN_TRANS_NAMESPACE));
		}
		$amount  = 0;
		foreach ($this->data['rooms'] as $roomid => $amount){
         if($amount){
				$wpdb->insert($wpdb->prefix.DATABASE_PREFIX.'bookings',
											array('idguest' => $guestid,
											'idroomtype' => $roomid ,
											'beds' =>$amount,
											'checkin' => date('Y-m-d',strtotime($this->data['startDate'])),
											'checkout'=>date('Y-m-d',strtotime($this->data['startDate'])+$this->data['noNights']*86400),
											'numberofnights'=>$this->data['noNights'],
											'invoice' => $this->invoice,
											'checkedin' => $checkedin
										));
				$total += $amount;
			}
		}
		return ($total > 0 ? array('success',__('booking recorded',PLUGIN_TRANS_NAMESPACE)) : array('fail',__('no rentals',PLUGIN_TRANS_NAMESPACE)));
	}
		
	/* insert and update of invoice table  */
	function insertInvoice($updateFields,$tempid = false){
		global $wpdb;	
		switch($_POST['action']){
			case 'checkin':
				$payment = $wpdb->get_var($wpdb->prepare('SELECT payment FROM '.$wpdb->prefix.DATABASE_PREFIX.'invoices WHERE invoice = '.$updateFields['invoice']));
				$updateFields['payment'] += $payment;
				$updateFields['totaldue'] = $_POST['nodiscount'];
				$updateFields['payment'] = $_POST['payment'];
				if ($_POST['clubcard'] == 'on' ) $updateFields['totaldue'] = $_POST['discounted'];
				$wpdb->update( $wpdb->prefix.DATABASE_PREFIX.'invoices', $updateFields,array( 'invoice' => $updateFields['invoice']));
				$status = array('success',__('cleint checkedin $'.$updateFields['payment'],PLUGIN_TRANS_NAMESPACE));
				$_POST['payedToDate'] = $updateFields['payment'];
				$this->ammendPaymentHistory($_POST,$updateFields['invoice']);
				break;
			case 'update':
				$payment = $wpdb->get_var($wpdb->prepare('SELECT payment FROM '.$wpdb->prefix.DATABASE_PREFIX.'invoices WHERE invoice = '.$updateFields['invoice']));
				$updateFields['payment'] += $payment;
				$updateFields['arivaltime'] = $_POST['time'];
				$wpdb->update( $wpdb->prefix.DATABASE_PREFIX.'invoices', $updateFields,array( 'invoice' => $updateFields['invoice']));
				$status = array('success',__('invoice updated total payed $'.$updateFields['payment'],PLUGIN_TRANS_NAMESPACE));
				$_POST['payedToDate'] = $updateFields['payment'];
				$this->ammendPaymentHistory($_POST,$updateFields['invoice']);
				break;
			case 'makepayment':
				$payment = $wpdb->get_var($wpdb->prepare('SELECT payment FROM '.$wpdb->prefix.DATABASE_PREFIX.'invoices WHERE invoice = '.$updateFields['invoice']));
				$updateFields['payment'] += $payment;
				$wpdb->update($wpdb->prefix.DATABASE_PREFIX.'invoices',$updateFields,array( 'invoice' => $updateFields['invoice'])); 
				$status = array('success',sprintf(__('total payed on this invoice $%d',PLUGIN_TRANS_NAMESPACE),$updateFields['payment']));
				$_POST['payedToDate'] = $updateFields['payment'];
				$this->ammendPaymentHistory($_POST,$updateFields['invoice']);
				break;
			case 'newbooking':
			default:
				$updateFields['arivaltime'] = $_POST['time'];
				
				$this->data['updatetime'] = time();
				$updateFields['updatetime'] = date('Y-m-d h:m:s',time());
				$temprec= $wpdb->get_var( $wpdb->prepare('SELECT totaldue FROM '.$wpdb->prefix.DATABASE_PREFIX.'invoices WHERE invoice = %s',$tempid));
				if(!empty($temprec)){
					$this->temprecorded = true;
					$status = array('fail',__('invoice already recorded',PLUGIN_TRANS_NAMESPACE));
				}
				if($tempid) $updateFields['invoice'] = $tempid;	
			
				$this->data['checkin'] = date('d-m-y',strtotime($this->data['checkin']));
				$this->data['checkout'] = date('d-m-y',strtotime($this->data['checkout']));
			
				$updateFields['paymentdata']  = serialize(array($this->data));
				$wpdb->insert($wpdb->prefix.DATABASE_PREFIX.'invoices',$updateFields); 
			
				$this->invoice = $updateFields['invoice'];
				$status =  array('success',__('invoice recorded',PLUGIN_TRANS_NAMESPACE));
			break;
		}
		return $status;
	}
	/* sanatise data and update payment history */
	private function ammendPaymentHistory ($newData,$invoice = false){
		global $wpdb;	
		if($invoice) {
			$current = $wpdb->get_var($wpdb->prepare('SELECT paymentdata FROM '.$wpdb->prefix.DATABASE_PREFIX.'invoices WHERE invoice = '.$invoice));
			$update = unserialize($current);
			$newData['updatetime'] = time();
			$newData['checkin'] = date('d-m-y',strtotime($_POST['checkin']));
			$newData['checkout'] = date('d-m-y',strtotime($_POST['checkout']));
			$update[] = $newData;
			$wpdb->update( $wpdb->prefix.DATABASE_PREFIX.'invoices', array('paymentdata'=> serialize($update)),array( 'invoice' => $invoice));
		}
	}
	

	/* replace shortcodes with reltive data for all templates*/
	//this should probably be part of template object
	function createReplacements($section){
		global $wpdb;
		$Vars = get_option('Dharma_Vars');
		
		if($this->siteVars['bookingState']=='testing') echo "<h1 style='border-bottom:2px solid gray;'>$section</h1>";
		$niceNights = $this->data["noNights"].' '.($this->data["noNights"]>1?__('nights',PLUGIN_TRANS_NAMESPACE):__('night',PLUGIN_TRANS_NAMESPACE));
		$nicePeople = $this->noPoeple.' '.($this->noPoeple>1?__('guests',PLUGIN_TRANS_NAMESPACE):__('guest',PLUGIN_TRANS_NAMESPACE));
		$money = ($this->balance == 0?__('total paid',PLUGIN_TRANS_NAMESPACE):$this->balance.' '.__('due',PLUGIN_TRANS_NAMESPACE));
		$this->replacements = array('[startdate]'=>$this->data["startDate"],
											'[checkin]'	=> $this->data['startDate'],
											'[nicestartdate]'=>date('l \t\h\e jS \of F \'y',strtotime($this->data["startDate"])),
											'[niceenddate]'=>date('l \t\h\e jS \of F \'y',strtotime($this->data["startDate"])),
											'[checkout]' => 'checkout',
											'[noofnights]' =>$this->data["noNights"],
											'[fullname]'=>$this->data["fullname"],
											'[phone]'=>$this->data["phone"],
											'[email]'=>$this->data["email"],
											'[time]'=>$this->data["time"],
											'[comment]'=>$this->data["comment"],
											'[totaldue]'=>money_format('%.2n',$this->totaldue),
											'[discountdue]'=>money_format('%.2n',$this->discountdue),
											'[balance]' => money_format('%.2n',$this->balence),
											'[payment]' => money_format('%.2n',$this->payment),
											'[totalpeople]' => $this->noPoeple,
											'[nicepeople]' => $nicePeople,
											'[nicenights]' => $niceNights,
											'[money]' => $money,
											'[adminEmail]' => $Vars['adminEmail'],
											'[adminphone]' => $Vars['smsPhone']
											);
											
		//smaller version of replacements used for email subjects and sms's 
		$this->replaceSmall = $this->replacements ;
 		$shortcodes = $wpdb->get_results( $wpdb->prepare( 'SELECT name,data FROM '.$wpdb->prefix.DATABASE_PREFIX.'templates WHERE `section` LIKE  \'%s\' AND `type` LIKE \'shortcode\'',$section ));

		foreach($shortcodes as $item ){
			switch($item->name){
				case 'roomtext':
					$this->replacements['[roomtext]'] = $this->makeRoomText($item->data);
				break;
			case 'textmessage'://lowercase...
				$this->replacements['[textmessage]'] =  str_replace('[phone]',$this->data['phone'] , $item->data);
				break;
			}
		}
	}
	/* creates the replacment info for [roomtext] shortcode*/
	private function makeRoomText($template){
		global $wpdb;
		$roomTextShortcodes = array('[nicepeople]','[roomname]','[price]','[discountprice]','[totalcost]','[totaldiscount]');
		foreach($this->data['rooms'] as $id => $amt ) {
			if(!$amt) continue;
			$row = $wpdb->get_row($wpdb->prepare('SELECT price,name,discount FROM '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes WHERE id='.$id));
			$nicePeople = $amt.' '.($amt>1?__('guests',PLUGIN_TRANS_NAMESPACE):__('guest',PLUGIN_TRANS_NAMESPACE));
			$return .= str_replace($roomTextShortcodes, 
														array($nicePeople,
																	$row->name,
																	$row->price,
																	$row->discount,
																	money_format('%.2n',	(($row->price*$amt)*$this->data["noNights"])), 
																	money_format('%.2n',	(($row->discount*$amt)*$this->data["noNights"])) 
																	), 
								$template);
		}
		return $return;
	}
		
/*
-----------------------------------------------------------------------
communication functoins email, sms
-----------------------------------------------------------------------
*/
	/* deciedes and starts all comunications */
	function doComunications(){
		global $wpdb;
		$baseVars = $this->siteVars;
		
		$officeEmail= $baseVars['adminEmail'];
		$replyToEmail = $baseVars['replyEmail'];
		
		//send client email
		$subject= $wpdb->get_var('SELECT data FROM '.$wpdb->prefix.DATABASE_PREFIX.'templates WHERE `section` LIKE  \'email\' AND name LIKE \'subject\'' );
		$body= $wpdb->get_var('SELECT data FROM '.$wpdb->prefix.DATABASE_PREFIX.'templates WHERE `section` LIKE  \'email\' AND name LIKE \'body\'' );
		$this->createReplacements('email');
		$this->sendAmail($officeEmail,$replyToEmail,$this->data['email'],
											str_replace(array_keys($this->replaceSmall),array_values($this->replaceSmall), $subject),
											str_replace(array_keys($this->replacements),array_values($this->replacements), $body)
											);
		//office email
		$subject= $wpdb->get_var(  'SELECT data FROM '.$wpdb->prefix.DATABASE_PREFIX.'templates WHERE `section` LIKE  \'notifications\' AND name LIKE \'subject\''  );
		$body= $wpdb->get_var(  'SELECT data FROM '.$wpdb->prefix.DATABASE_PREFIX.'templates WHERE `section` LIKE  \'notifications\' AND name LIKE \'body\''  );
		$this->createReplacements('notifications');
		$this->sendAmail($this->data['fullname'].'<'.$this->data['email'].'>',$this->data['email'],$officeEmail,
											str_replace(array_keys($this->replaceSmall),array_values($this->replaceSmall), $subject),
											str_replace(array_keys($this->replacements),array_values($this->replacements), $body),
											true
											);
											
		//sms comuncations
		$smsVars = $this->siteVars ;
		if($smsVars['smsState'] == 'up'){	
			$this->createReplacements('sms');
			if(((strtotime($this->data['startDate']) - time())/3600) > $smsVars['SMS_Hours']){
				$data= $wpdb->get_var( $wpdb->prepare( 'SELECT data FROM '.$wpdb->prefix.DATABASE_PREFIX.'templates
																						WHERE `section` LIKE  \'sms\' AND name LIKE \'officesms\'' ) );
				if($this->siteVars['bookingState'] == 'testing') echo '<h2>Office sms</h2>';
				$this->sendAsms($smsVars['smsPhone'], $this->data['phone'], stripslashes(str_replace(array_keys($this->replaceSmall),array_values($this->replaceSmall), $data)));
			} 
			if($this->data['checkbox']["textmessage"]){
				$data= $wpdb->get_var(  'SELECT data FROM '.$wpdb->prefix.DATABASE_PREFIX.'templates WHERE `section` LIKE  \'sms\' AND name LIKE \'clientsms\'' );
				if($this->siteVars['bookingState'] == 'testing') echo '<h2>Client sms</h2>';
				$this->sendAsms($this->data['phone'],
													$smsVars['smsPhone'],
							stripslashes(str_replace(array_keys($this->replaceSmall),array_values($this->replaceSmall), $data))
													);
			}
		}
	} 	
	/* get temlate and send to sms object */
	private function sendAsms($to, $from,$message){
		$smsVars = $this->siteVars ;
		
		if($this->siteVars['bookingState'] == 'testing'){
			echo "<strong>to: </strong>$to<br />
						<strong>from: </strong>$from<br />
						<strong>message: </strong>$message<br />";
		}else{
			// add other sms api's here 
			include_once(PLUGIN_ROOT_PATH.'libs/sms_api.php' );
			$mysms = new sms($smsVars);
			$mysms->session;
			$mysms->send ($to,$from,$message );
		}
		$this->feedback[] = array('success', __('SMS sent too '.$to,PLUGIN_TRANS_NAMESPACE));
	}
	/* puts together email and sends it, note does not make email template */
	private function sendAmail($from,$replyTo,$to,$subject,$message,$admin = false){
	   $header  = 'From: '.$from.''."\r\n".'Reply-To: '.$replyTo.''."\r\n";
		$header .= 'MIME-Version: 1.0' . "\r\n".'Content-type: text/html; charset=iso-8859-1' . "\r\n";		
		
		if($this->siteVars['bookingState'] == 'testing'){
			echo "<small>".__('header',PLUGIN_TRANS_NAMESPACE).": $header</small><br />
			<small>".__('to',PLUGIN_TRANS_NAMESPACE).":$to</small> <br />
			<small>".__('subject',PLUGIN_TRANS_NAMESPACE).": $subject</small><br />
			<small>".__('message',PLUGIN_TRANS_NAMESPACE).":</small><br />$message";
			mail($to, $subject, $message, $header);
		}else{
			mail($to, $subject, $message, $header);
		}
		if($admin) $this->feedback[] = array('success', sprintf(__('Email sent %s',PLUGIN_TRANS_NAMESPACE),$to));
	}
//----------------------------------------------------------------------------------------------------------[end commuincation functions ]


	/* calculate and return the total and discount total */
	function getTotals(){
		global $wpdb;
		$total = 0;
		if (empty($this->data['rooms'])) {
			$this->feedback[] = array('fail',__('Pease select at least one room',PLUGIN_TRANS_NAMESPACE));
			return false;
		}
		foreach($this->data['rooms'] as $id => $amt ) {
			if($amt >0){				
				$price = $wpdb->get_row($wpdb->prepare('SELECT price,discount FROM '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes WHERE id=%d',$id));
				$total += $price->price * $amt*$this->data['noNights'];
				$dtotal += $price->discount * $amt*$this->data['noNights'];
			}
		}
		return array($total,$dtotal);
	}
	/* shows the feedback */
	function showFeedback (){
			?><div id="feedback"><ul><?php foreach($this->feedback as $feed): ?> 
				<li class="<?=$feed[0]?>"><?=$feed[1]?></li>
			<?php	endforeach ?></ul></div><?php 
		
	}
}
