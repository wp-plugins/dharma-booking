<?php 
class templateEngine extends dharmaAdmin 	{
	function templateEngine(){
		global $wpdb;
		$this-> includeCSSnDivs();
		$this->includeScripts();
		
		
		$this->section = (!empty($_GET['section'])?$_GET['section']:'email');
		if($_POST) $this->saveTemplate();
		$this ->makeMenu(array('email','notifications','final page','SMS'), 'email');
		$sql = 'SELECT id,name,type,data,label
						FROM '.$wpdb->prefix.DATABASE_PREFIX.'templates 
						WHERE  `section` LIKE  \''.$this->section.'\' 
						ORDER BY weight';
		$this->display($wpdb->get_results( $sql));
	}
	function saveTemplate(){
		global $wpdb;
		foreach($_POST['data'] as $k => $v){
			$wpdb->update($wpdb->prefix.DATABASE_PREFIX.'templates', array( 'data' => stripslashes($v)), 
				array( 'section' => $this->section, 'name' => $k )
			);
		}
	}
	
	function display($input){
		if(!is_array($input)) return;
		?>
			<form id="templateForm" action="" method="post">
			<input type="hidden" name="section" value="<?=$this->section?>" />
		<?php 
		for($i=0; $i < count($input); $i++){
			$item = $input[$i];// work around cos foreach was erroring...
			switch($item->type){
				case 'body':
					?>
					<div>
						<label for="<?=$item->name?>">
							<span class="shortcodetitle up">&gt;</span>
							<span class="shortcodetitle down hidden">v</span>
							<?=$item->label?>
						</label>
						<div class="sortcodes hidden ">[startdate] [checkin] [checkout] [noofnights] [fullname] [phone] [email] [time][comment] [totaldue] [discountdue] [balance] [payment] [totalpeople] [nicepeople] [nicenights] [money] [roomtext] [textmessage]</div>
					</div>
					<?php 
					wp_editor( stripslashes($item->data), 
													'data['.$item->name.']', 
													array( 'textarea_name' => 'data['.$item->name.']', 
													'media_buttons' => true, 
													'teeny' => true));
					break;
				case 'shortcode':
					?>
					<div style="float:left; margin-right:5px;">	
						
						<label for="<?=$item->name?>">
							<span class="shortcodetitle up">&gt;</span>
							<span class="shortcodetitle down hidden">v</span>
							[<?=$item->name?>] shortcode	
						</label>
						<div class="sortcodes hidden ">
							<?php if($item->name=='roomtext'):?>
								[nicepeople] [roomname] [price] [discountprice] [totalcost] [totaldiscount]
							<?php else :?>
								[phone]
							<?php endif ?>
							</div>
						<textarea style="width:500px;height:100px;" name="data[<?=$item->name?>]"><?=$item->data?> </textarea>
					</div>
					<?php
					break;
				case 'blurb'://unused at present 
					?><p><?=$item->data?></p><?php
					break;
				case 'subject':
					?>
					<div>
						<label for="<?=$item->name?>">
							<span class="shortcodetitle up">&gt;</span>
							<span class="shortcodetitle down hidden">v</span>
							<?=$item->label?>
						</label>
						<div class="sortcodes hidden ">[startdate] [checkin] [checkout] [noofnights] [fullname] [phone] [email] [time] [comment] [totaldue] [discountdue] [balance] [payment] [totalpeople] [nicepeople] [nicenights] [money] </div>
					</div>
					<input style="width:900px;height:18px;" name="data[<?=$item->name?>]" value="<?=$item->data?>"/>
					
							
					
						<?php
			}
		}
		?>
		<div class="clear"></div><input type="submit" value="Save" /></form>
		<h2>This page is used to build the <?=$section?> templates.</h2>
		<h4>Shortcodes are used as place holders for dynamic text and will be replaced in the final version with there approprate value, for example  [fullname]  will be replaced by the users full name as enterd on the form.<br />
		The [roomtext] [textmessage] are replaced with there shortcode if listed on the page.<br /> 
		All shortcodes are lowercase.</h4>
		<?php
	}
}
