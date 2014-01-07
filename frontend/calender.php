<?php 
require_once('functions.php');
class matrixCalender {
	function matrixCalender () {
		
		//get back end settings 
		$this->settings = get_option('Dharma_Vars');
		$this->gateway = $this->settings['paymenttype'];
		$this->live = true;
		$this->cssOvers = $this->settings['css'];
		if($this->settings['bookingState'] == 'down') {echo $this->settings['OfflineText']; $this->live = false;return;  } 
		
		//put arrays in place 
		$this->vars = getStrings();
		$this->roomtypes = getRentalFullDetails();
		
		$this->url = PLUGIN_ROOT_URL;
		
      //setup important values for sure
      $this->noNights = 	$this->settings['CnoNite'];
		$this->weekStart = date('Y-m-d', time() + (86400 * $this->settings['cDaysAhead'])); 
		if(!empty($_GET['noNights'])){
			$this->noNights = 	$_GET['noNights'];
			$this->weekStart = $_GET['startDate'];
		}elseif(!empty($_POST['noNights'])){
			$this->weekStart  	 = $_POST['weekStart'] ;
			$this->noNights = 	$_POS['noNights'];
		}
		$this->weekStartdisp = date('d M Y', strtotime($this->weekStart));

		$this->startStamp = strtotime($this->weekStart );
		$this->endStamp = $this->startStamp  + (86400* $this->settings['CnoDays']);
		$this->thanksPage = $this->settings['thanksPage'];
    }
	
	/* displayes the page and sets everything up */
	function displayAll (){
		
		if(!$this->live) return;
		includeFrontendScripts('calender',$this->vars['interface'],$this->settings);
		includeFrontendCSS();
		$this->cssOvers;
      
      if($this->settings['bookingState'] == 'testing') echo $this->settings['TestingText'];
		?>
      <script type="text/javascript">var discountCard = '<?=$this->settings['discountCard']?>';</script>		
      <div id="booking-calendar">
		<div id="booking-plugin-blackout" ></div>
		<noscript><h1 class="errors"><?=__('This page requires javascript to use.',PLUGIN_TRANS_NAMESPACE)?></h1></noscript>
		
		<?php if($this->settings['updateTimeoutOn'] == 'yes') : ?>
		<small class="floatright" id="timer"><?=sprintf(__('Updating in %s seconds.',PLUGIN_TRANS_NAMESPACE),'<b id="time"></b>');?></small	>
		<?php endif ?>
		<form id="calendarForm" method="POST" action="<?=get_permalink($this->thanksPage);?>" >
			<input type="hidden" id="nicelyFormatedDate" value="<?=date('jS \of F Y',$this->startStamp)?>" />
			<div id="dateInput">
				<div class="forminput"><h2>
					<label for="startDate" class="text" ><?php _e('Ariving',PLUGIN_TRANS_NAMESPACE);?></label>
					<input type='text' id='startDate' name='startDate' value='<?=$this->weekStartdisp ?>' />
					<label for="noNights" class="text" ><?php _e('Nights',PLUGIN_TRANS_NAMESPACE);?></label>
					<select id="noNights" name="noNights">
						<?php for($i=1;$i<$this->settings['CnoDays']+1;$i++) :?>
							<option <?=($this->noNights == $i?'selected="selected"':'')?>><?=$i?></option>
						<?php endfor ?>
					</select>
				</h2></div>
			</div>
			
			<div id="calenderContainerDiv">
            <?php if($this->settings['showpopout']): ?>
				<div id="popoutCalendar" class="hidden" style="<?=$this->settings['calenderpopoutcss']?>" >
					<? foreach ($this->roomtypes as $id => $room) : ?>
						<div id="<?php echo $id ?>-Info" class="hidden rentalinfobox">
							<?=stripslashes( $room['discription'])?>
						</div>
					<? endforeach ?>
				</div>
            <?php endif ?>
				<div id="calenderDiv"><?php $this->showCalendarMatrix();?></div>
				<button type="button" id="continue-button" class="floatright hidden"><?php _e('Continue',PLUGIN_TRANS_NAMESPACE);?></button>
			</div>	
			
			<div id="formFields" class="hidden">
				<?php echo makeInputs(userInfoDetails()); ?>
				<div class="clear"></div>
				<div id="callender-button-div">
					<button id="finalCalendarButton" type="button" >
						<big><?php _e('Book Now',PLUGIN_TRANS_NAMESPACE);?></big><br />
						<small>
							<?php _e('Complete required(*) fields',PLUGIN_TRANS_NAMESPACE);?>
						</small>
					</button>
               <div id="errorDiv" class="hidden"><strong><?=__('Please enter',PLUGIN_TRANS_NAMESPACE)?></strong><ul id="errorList"></ul></div>
					<div class="clear"></div>
				</div>
			</div>
		</form>
		<div class="clear"></div>
		<?php if($this->gateway == 'none'):?>
			<form id="paymentGatewayForm" action="<?=get_permalink($this->thanksPage)?>" method="post" class="hidden">
				<input type="hidden" name="invoice" id="invoice" value="0" />
				<input type="hidden" name="payed"  value="0" />
				<input   type="submit" value="Book Now" id="makeBookingButton"/>
			</form>
		<?php else: ?>
			<div id="gateway-div" class="hidden displaybox" ><div id="gateway-inner">
				<ul id="final-details-overview"></ul>
				<div id="final-payment-overview"></div>sprintf
				<small>
					<?=sprintf(__('Prices are in %s &amp; per person per night',PLUGIN_TRANS_NAMESPACE),$this->settings['payment_currency_code']);?><br />
					<?php if($this->settings['discountCard'] != 'none'):?>
						<?=sprintf(__('Discount prices available upon check in.',PLUGIN_TRANS_NAMESPACE),$this->settings['discountCard'],$this->settings['discountCard']);?>
					<?php endif ?>
				</small>
				<div class="clear"></div>
				<?php  
				include_once(PLUGIN_ROOT_PATH.'frontend/gateways/'.$this->gateway.'.php');
				if($this->settings['takeFull']){
					echo '<script type="text/javascript">var takeDeposit = false;</script>';
               makePaymentForm($this->settings['paymentAccount'],$this->settings['payment_currency_code'],'full');
            }
            if($this->settings['takeDeposit']){
					echo '<script type="text/javascript">var takeDeposit = '.$this->settings['payment_depoist'].'</script>';
               makePaymentForm($this->settings['paymentAccount'],$this->settings['payment_currency_code'],'deposit',$this->settings['payment_depoist']);
            }
				?>
			</div></div>
		<?php endif ?>
		</div>
	<?php
	}
	
	
	/*
	displays the reantal calender
	used as part of display and as ajax call
	*/
	function showCalendarMatrix(){	
		$this->firstAvailble = $this->endStamp;
		?>
		<table id="rentalCalendar" cellspacing="0" border="0">
			<tr class="header nohover">
				<td   colspan="<?=($this->settings['discountCard'] != 'none'?'3':'2')?>"></td>
				<?php $n=1; 	for($i=$this->startStamp; $i<$this->endStamp; $i += 86400):?>
					<td class="<?php echo ($n <= $this->noNights ?'active':'inactive'); ?>-day"><?=date('j',$i)?></td>
				<?php  $n++; endfor; ?>
			</tr>
		<?php  
		foreach ($this->roomtypes as $id => $room) : 
			$details = $this->createRentalRow($room['minimum'],$id );
		?>
		<tr >
 			<th id="<?=$id?>" class="rentalRow">
				<span id="<?=$id?>-name"><?=$room['name']?></span> 
            <?php if($this->settings['showpopout']): ?><img src="<?=$this->url ?>img/info.png" /><?php endif ?>
			</th>		
			<th>
				<?=$room['price']?> 
				<input type="hidden" value="<?=$room['price']?>" id="price_<?=$id?>" />
			</th>
			<?php if($this->settings['discountCard'] != 'none'):?>
				<th>
					<?=$room['discount']?> 
					<input type="hidden" value="<?=$room['discount']?>" id="discount_<?=$id?>" />
				</th>	
			<?php endif?>
			<?php echo $details[0]; ?>
			
			<td><?=$details[1];?></td>
			</tr>
			<? endforeach ?>
			<?php if($this->settings['discountCard'] != 'none'):?>
				<tr class="nohover">
					<td colspan="2"></td>
					<td>
						<img src="<?=$this->url ?>img/discountcards/<?=$this->settings['discountCard']?>.png" 
									title="<?=__('bbh prices',PLUGIN_TRANS_NAMESPACE)?>" 
									alt="<?=$this->settings['discountCard']?>" />
					</td>
				</tr>
			<?php endif?>
		<?php if($this->rentalsThatAreAvaliable < 1) : ?>
			<tr><td colspan="20"><div id="non-avaliable"><p><strong>
				<?=sprintf(__('There is no avalibty for one or more of your dates, the earliest avalible date is %s the %s of %s.',PLUGIN_TRANS_NAMESPACE),date('l',$this->firstAvailble),date('jS',$this->firstAvailble), date('F',$this->firstAvailble));?>
			</strong></p></div></td></tr>
		<?php endif ?>
		</table>
		<div id="bottomOfCalendar"></div>
		<div id="reviewDiv" class="hidden" >
			<table id="reviewTable"><tbody>
				<tr><th colspan="4" id="reviewTitle"></th></tr>
				<tr>
					<th>Total</th>
					<th id="reviewTotal"></th>
					<th><?=__('$',PLUGIN_TRANS_NAMESPACE)?><span id="totalPrice"></span></th>
               
               <?php if($this->settings['discountCard'] != 'none'):?>
					<th>
						<img src="<?=$this->url?>img/discountcards/<?=$this->settings['discountCard']?>.png" 
									title="<?=__('total with discount card',PLUGIN_TRANS_NAMESPACE)?>" 
									alt="<?=$this->settings['discountCard']?>" />
                  <?=__('$',PLUGIN_TRANS_NAMESPACE)?><span id="discountTotal"></span>
					</th>
               <?php endif ?>
				</tr>
			</tbody></table>
			<div class="clear"></div>
		</div>
    <?php
    }
    /*
    create and return each callender rows avaliblity
    */
    function createRentalRow ($roomMin,$roomId){
			$rentalAvaliblity= '';
			$n = 0;
			$t = 1;
			$lowestAvail = 99999;

			//this loop is done the wrong way round, it should make one call for all the data then proccess that 
			for($i=$this->startStamp; $i<$this->endStamp; $i += 86400){
            $data = $this->getRentalOptionsMatrix	($i,($i+86400),$roomId);
				$id = $data['rentalId'];
            if ($data['availab'] > 0  && $data['availab'] >= $roomMin ){
               $class	=	'avaliable';
					$avali	= $data['availab'];
					if($i < $this->firstAvailble )
						$this->firstAvailble = $i;
            }else {
               $class	= 'full';
               $avali = 'x';
            }
            if($t > $this->noNights){
					$class = 'disabled'; 
            }else{
               if($data['availab'] < $lowestAvail && $lowestAvail != 0 ) 
						$lowestAvail =  $data['availab'];
            }
            $rentalAvaliblity .= '<td id="rentalbox'.$data['rentalId'].$n.'" class="'.$class.'">'.$avali.'</td>';
            $n++;
				$t++;
			} 
			if($lowestAvail >= $roomMin ) {
            $options = '';
            for ($k = $roomMin; $k <= $lowestAvail; $k++){
                $options .='<option value="'.$k.'">'.$k.' '.( $k < 2 ? $this->vars['interface']['Guest'] : $this->vars['interface']['Guests']).'</option>';
            }
            $rentalSelect = '<select id="'.$id.'"  class="rentalSelector" name="rooms['.$id.']" >
                            <option value="" selected="selected" >'.$this->vars['interface']['Select'].'</option>'.
									 $options.
									 '</select>';
				
				$this->rentalsThatAreAvaliable++;
			} elseif($this->settings['showreserved']) {
            $rentalSelect = '<select disabled="disabled" class="inputDis"><option>'.__('Reserved',PLUGIN_TRANS_NAMESPACE).'</option></select>';
			}
        return array($rentalAvaliblity, $rentalSelect);
    }

/*
the sql call
*/
function getRentalOptionsMatrix ($in,$out,$theID){	
    $from  = date('Y-m-d', $in);
    $to  = date('Y-m-d', $out);
    global $wpdb;
    $sql =
			"SELECT
				R.id AS rentalId,
				R.minimum AS min,
				R.capacity AS cap,
				R.discount,
				R.capacity - SUM(B.beds) AS availab
			FROM  	(SELECT
					checkin AS date FROM ".$wpdb->prefix.DATABASE_PREFIX."bookings
					UNION SELECT checkout FROM ".$wpdb->prefix.DATABASE_PREFIX."bookings
					UNION SELECT DATE('$from')) D
				LEFT JOIN (SELECT id, idguest, idroomtype, beds, checkin, checkout FROM ".$wpdb->prefix.DATABASE_PREFIX."bookings UNION
						SELECT 0 /*id*/, 0 /*idguest*/, id /*idroomtype*/, 0 /*beds*/,
						DATE('$from'), DATE('$to') FROM ".$wpdb->prefix.DATABASE_PREFIX."roomtypes) B
					ON D.date BETWEEN B.checkin AND ADDDATE(B.checkout, -1)
				LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."roomtypes R
					ON B.idroomtype = R.id
			WHERE 
				D.date BETWEEN '$from' AND ADDDATE('$to', -1) AND R.id = '".$theID."'
			GROUP BY
				D.date,
				B.idroomtype
			ORDER BY
				B.idroomtype,
				D.date";
		return $wpdb->get_row($sql,  ARRAY_A);
	}
}
?>
