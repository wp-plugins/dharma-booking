<?php
/*next one up for a complet rework !*/
require_once('dharmaAdmin.php');

global $wpdb;

$pluginUrl = PLUGIN_ROOT_URL;
$dharmaAdmin = new dharmaAdmin();
$addItemOptions = array('menu Order','item name','minimum','capacity','price','discount','discription');

if (isset($_POST['action']) && $_POST['action'] == 'delete') {
	$wpdb->update( $wpdb->prefix.DATABASE_PREFIX.'roomtypes',	array('active' => 0), array('id' => $_POST['deleteItemId'] ));
}
if (isset($_POST['itemadd']) && $_POST['itemadd'] == 'yes') {
		$wpdb->insert( 
				$wpdb->prefix.DATABASE_PREFIX.'roomtypes', 
					array( 
						'menuorder' => $_POST['menu_Order'],
						 'name' => $_POST['item_name'],
						 'minimum' => $_POST['minimum'],
						 'capacity' => $_POST['capacity'],
						 'price' => $_POST['price'],
						 'discount' => $_POST['discount'],
						 'discription' => $_POST['discription']
						), 
						array( '%d', '%s','%d','%d','%d','%d','%s') 
			);
}
$roomtypes = $wpdb->get_results("SELECT id,menuorder,name,minimum,capacity,price,discount,discription FROM ".$wpdb->prefix.DATABASE_PREFIX."roomtypes WHERE active = 1 ORDER BY menuorder",ARRAY_A );

$dharmaAdmin->includeCSSnDivs();
$dharmaAdmin->includeScripts();
?>

<script type="text/javascript">
var saveRentalAjax = "<?=$pluginUrl?>admin/ajax/saveRental.php"

//after rework of functions it can go into main scripts file, if they are nice 
jQuery(function () {
	jQuery(".saveRentalButton").click(function(){
		jQuery('#responce-box').html('Saving...');
		SaveRental(jQuery(this).closest("tr").find('form').serialize(),jQuery(this).closest("tr"));
	});
	jQuery("input[type=button].rentalcloseButton").click(function () {
	  	jQuery('#info-'+this.id).hide('slow', function() { });
     	jQuery('#blackout').hide('fast', function() { });	
		jQuery('#'+this.id).addClass("edited");
		jQuery('#'+this.id).val("edited");
	});
	jQuery("input[type=button].editButton").click(function () {
	  	jQuery('#info-'+this.id).show('slow', function() { });
     	jQuery('#blackout').show('fast', function() { });	
	});

	jQuery("input[type=button]#addButton").click(function () {
     	jQuery('#addItemDiv').show('slow', function() { });
     	jQuery('#blackout').show('fast', function() { });
	});
	jQuery("input[type=button]#addItemButton").click(function (){
      jQuery("#addItemForm").submit();
	});
	jQuery('.deleteButton').click(function () {
		var nameText = jQuery(this).closest('tr').find('.name').val();
     	if (confirm("Are you sure you want to delete \""+nameText+"\" ?")) {
			jQuery('#deleteItemId').val(jQuery(this).closest("tr").find('.rentalId').val());
   	   jQuery('#deleteItemForm').submit();
		}
		
		return false;
	});
	jQuery('#saveEditButton').click(function(){
		jQuery(this).closest("table").find("input[type=text]").clone().appendTo("#theform");
		jQuery(this).closest("table").find("select").clone().appendTo("#theform");
      jQuery('body').css('cursor','wait');
	});
});
</script>
<table  class="rentalRow floatleft" cellspacing="0">
	<tr>
		<th>Order</th>
		<th>Name</th>
		<th>Minimum</th>
		<th>Capacity</th>
		<th>Price</th>
		<th>Discount</th>
		<th>Discription</th>
	</tr>
	<?php foreach ($roomtypes as $roomtype) { ?>
		<tr>
			<form>
			<?php foreach ($roomtype as $fieldName => $value) { ?>
				<?php if ($fieldName == 'id') $theId = $value; ?>
				<?php if ($fieldName == 'discription') :	?>
					<td> <input type="button" value="edit" class="editButton" id="<?php echo $theId ?>"/>  
						<span class="hidden popupup-box" id="info-<?php echo $theId ?>">
						<textarea name="dscription" id="disc<?=$theId?>"> <?=stripslashes($value)?></textarea>
						<button class="cancelButton" type="button"><?=__('x',PLUGIN_TRANS_NAMESPACE)?></button>
						<div class="clear"></div>	  
						</span>
					</td>
				<?php elseif($fieldName == 'id') : ?>
					<input type="hidden" name="id" value="<?=$roomtype['id']?>" id="itemid"/>
				<?php else :?>
					<td><input type="text" name="<?=$fieldName?>" value="<?=$value?>" class="<?=$fieldName?>" /> </td>
				<?php  endif ?>
			<?php } ?>
			<td><button type="button" class="saveRentalButton">Save</button></td>
			<td>
				<input type="hidden" value="<?= $theId?>" class="rentalId" />
				<button class="deleteButton" >Delete</button>
			</td>
		</tr>
		</form>
	<?php } ?>
</table>

<h1 id="responce-box"></h1>

<div class="clear"></div>
<h2><input  type="button" id="addButton" value="<?=__('Add an rental',PLUGIN_TRANS_NAMESPACE)?>" /></h2>

<form id="deleteItemForm" method="POST" action=""> 
	<input type="hidden" name="deleteItemId" id="deleteItemId" />
	<input type="hidden" name="action" value="delete" /> 
</form>

<div id="addItemDiv" class="popupup-box">
	<h2>Add a new rental Item</h2>
	<form id="addItemForm"  method="POST" action="">
		<input type="hidden" name="itemadd" value="yes" />
		<?php foreach($addItemOptions as $anOption ): ?>
			<div class="clear">
			  <label for="<?=$anOption?>"><?=ucFirst($anOption)?></label>
			  <input type="text" id="<?=$anOption?>" name="<?=$anOption?>"/>
			</div>  
		<?php endforeach ?>
		<h3>
			<input type="button" value="Cancel" id="cancelButton" /> 
			<input type="button" value="Add" id="addItemButton" />
		</h3>
	</form>
</div>
