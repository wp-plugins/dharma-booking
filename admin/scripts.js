var changedRooms = {};
var line1=[];
var expected = [];
var payments = [];
var chartUpdatePage

jQuery(function () {

	var totalRentals = 0;
	var totalDiscount = 0;
	var noNights = 1;
	var total =[];
	
	var currentTime = new Date()
	var month = new Array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
	var today  = currentTime.getDate()+' '+month[currentTime.getMonth()]+' '+currentTime.getFullYear();
	
	var newbookingDays = jQuery('#newbookingDays').html();

	jQuery("#startDate,#endDate,.datepicked").datepicker({dateFormat: "dd M yy"});
	jQuery(".limiteddate").datepicker({dateFormat: "dd M yy", minDate: today});
		
	jQuery("input[type=text]").change(function(){jQuery(this).addClass("edited");});
	jQuery('#feedback').mouseleave(function(){jQuery(this).slideUp('fast')});
//-------------------------------------------------------------------------------------------------------------[clicks actions] 

//core
	jQuery('.popupup-box').on('click', '.cancelButton,#blackout',function(){
		jQuery('#blackout').fadeOut("fast");
		jQuery('.popupup-box').fadeOut("fast");
	});
	jQuery('#blackout').click(function(){jQuery('#blackout').fadeOut("fast");jQuery('.popupup-box').slideUp("fast");});
	
	jQuery('.popupup-box').on('click', ".saveButton",function () {
		jQuery('#'+this.id+'Form').submit();
	});
	
	jQuery('.popupup-box').on('click', ".deleteButton",function () { 
		if (confirm("Are you sure you want to delete this? This action can not be undone.")) {
			jQuery('#'+this.id+'Form').submit();
			}
	});
   
	/*
	--------------------------------------------------
	checkin dashboard page
	--------------------------------------------------
	*/
	jQuery('.popupup-box').on('click', ".closehistory",function () {
			jQuery('#paymenthistorybox').fadeOut('fast');
		return false;
	});
	jQuery('.popupup-box').on('click', ".paymentHistoryButton",function () {
		jQuery('#paymenthistorybox').fadeIn('fast');
		return false;
	});
	jQuery('.popupup-box').on('click', ".paymentButton",function () {
		jQuery("#makepaymentform .invoice").val(jQuery(this).closest("form").find('.invoiceid').val());
		jQuery("#makepaymentform .payment").val(jQuery(this).closest("form").find('.payment').val());
		jQuery('#makepaymentform').submit();
	});
	jQuery('.occupacityButton').click(function(){
		jQuery('#occupacityDisplay').fadeIn('fast'); 
		jQuery('#blackout').fadeIn('fast');
		jQuery('.ocupancity-chart').fadeOut('fast');
		jQuery('#'+this.id+'chart').fadeIn('fast');
		doOccupacityChart(this.id);
	});
	jQuery('#laundryButton').click(function(){
		jQuery('#laundryDisplay').fadeIn('fast');
		jQuery('#blackout').fadeIn('fast');
	});
	jQuery('#calenderButton').click(function(){
		jQuery('#calenderDisplay').fadeIn('fast');
		jQuery('#blackout').fadeIn('fast');
	});
	jQuery('#addNewBookingButton').click(function(){
		jQuery('#newbookingdiv').fadeIn('fast');
		jQuery('#blackout').fadeIn('fast');
	});
	jQuery('.checkinButton').click(function() { 
		jQuery('#checkinDiv').fadeIn("slow");    
		jQuery('#blackout').fadeIn("fast");  
		jQuery("#checkinInvoice").val(jQuery(this).closest("div").find('.invoice').val());
		jQuery("#checkinDue").val(jQuery(this).closest("div").find('.balancePos').val());
		
		jQuery('#checkinName').html(jQuery(this).closest("div").find('h1').html());
		
		jQuery("#checkinDueLabel").html(jQuery(this).closest("div").find('.balancePos').val());
		jQuery("#discountdue").val(jQuery(this).closest("div").find('.balancePos').val());
		jQuery("#checkinDiscountLabel").html(jQuery(this).closest("div").find('.discountdue').val());
		jQuery("#totaldue").val(jQuery(this).closest("div").find('.discountdue').val());
	});
	jQuery('#checkinclubcard').change(function() {
		if(this.checked) {
			jQuery("#checkinDue").val(jQuery("#checkinDiscountLabel").html());
		}else{
			jQuery("#checkinDue").val(jQuery("#checkinDueLabel").html());
		}
	});
	jQuery('#rentals-selectors input[type=number]').change(function () { 
		totalRentals = 0;
		totalDiscount = 0;
		jQuery('#rentals-selectors input[type=number]').each(function(theselector){
			totalRentals +=   (jQuery(this).closest("div").find('.price').html() * jQuery(this).val());
			totalDiscount += (jQuery(this).closest("div").find('.discount').html() * jQuery(this).val());
		});
		jQuery('#ptotal').html(totalRentals*newbookingDays);
		jQuery('#dtotal').html(totalDiscount*newbookingDays);
	});
	jQuery('#newbookingForm .limiteddate').change(function(){
		var val = jQuery('#newbookingForm #checkin').val();
		var split = val.split(' ');
		var checkin = new Date(split[2], month.indexOf(split[1]), split[0]);

		val = jQuery('#newbookingForm #checkout').val();
		split = val.split(' ');
		var checkout = new Date(split[2], month.indexOf(split[1]), split[0]);
		
		newbookingDays = parseInt((checkout-checkin)/(24*3600*1000));
		jQuery('#newbookingDays').html(newbookingDays);
		jQuery('#ptotal').html(totalRentals*newbookingDays);
		jQuery('#dtotal').html(totalDiscount*newbookingDays);
	});
	jQuery('#weekchart,#monthchart').bind('jqplotDataClick', function (ev, seriesIndex, pointIndex, data) {
		var dataz = String(data).split(/,/);
		jQuery('#dayDetailsBox').fadeIn("fast");
		var img = document.createElement("img");
		img.src = pluginUrl+'img/loading-hoz.gif';
		img.alt = 'loading';
		jQuery('#dayDetailsBox').html(img)
		if(seriesIndex != 0 ){
			getRentalDetailsSmall(dataz[2],seriesIndex);
		}else{
			getDayDetails(dataz[2])
		}
	});
	 
	//& reports pages
	jQuery(".detailsButton").click(function(){
		jQuery('#blackout').fadeIn("fast");
		jQuery('#blackout').html('<h1>Loading</h1>');
		getRentalDetails(jQuery(this).closest("div").find('.invoice').val());	
	 });	
	
	
	//template page
	jQuery('.shortcodetitle').click(function(){
		jQuery(this).closest("div").find('.sortcodes').slideToggle('fast');
		jQuery(this).closest("div").find('.up').fadeToggle('fast');
		jQuery(this).closest("div").find('.down').fadeToggle('fast');
	});

   //wordpress settings page 
   jQuery('#settingspage h3').click(function(){
		jQuery(this).next('table').toggle("fast");
   });
});



// some functions
function saveChanges () {
    var allChanges = [];
    jQuery.each(changedRooms, function (roomId) {
        var roomChanges = [];
        jQuery.each(this, function (dayNo) {
            roomChanges.push('"'+parseInt(dayNo)+'":'+parseInt(this));
        });
        allChanges.push('"'+parseInt(roomId)+'":{'+roomChanges.join(',')+'}');
    });
    allChanges = allChanges.join(',');
    jQuery("#availabChanges").val('{'+allChanges+'}');
    return !!allChanges;
}
function addBooking () {
    jQuery("#saveEditButton").attr("disabled", false);
    jQuery('#newbookingdiv').show('slow', function() { });
    jQuery('#blackout').show('fast', function() { });

   document.getElementById('checkinnamediv').innerHTML = name;
   document.getElementById('checkinpayed').innerHTML = payed;
   document.getElementById('checkintotal').innerHTML = total;

   document.getElementById('checkinbalance').innerHTML = (total - payed);
   bookingids.forEach(printArray);

}

function deleteBooking (idBooking) {
    if (confirm("Are you sure you want to delete this booking? this can not be undone.")) {
        jQuery("#deleteBookingId").val(idBooking).closest("form").submit();
    }
}



function cancelEdit () {
    jQuery('#newbookingdiv').hide('slow', function() { });
    jQuery('#editbookingdiv').hide('slow', function() { });
    jQuery('#checkindiv').hide('slow', function() { });
    jQuery('#blackout').fadeOut("fast");
    
    jQuery("#saveEditButton").attr("disabled", true);
    jQuery("#editBooking input[type=text], #editBooking select").val("");
    jQuery("#showGuest span").text("");
}

// ==================================================[ajax calls]
function getRentalDetailsSmall(input,type){
	var request = jQuery.ajax({url: getRentalDetailSmallsAjax,type: "POST",data: {type: type,invoice: input},cache: false,dataType: 'text'});
	request.done(function(msg) {jQuery('#dayDetailsBox').html(msg);jQuery('#blackout').html('');});
	request.fail(function(jqXHR, textStatus) {alert(textStatus+ "Request failed, try again" );});
}
function getRentalDetails(input){
	var request = jQuery.ajax({url: getRentalDetailsAjax,type: "POST",data: {invoice: input},cache: false,dataType: 'text'});
	request.done(function(msg) {jQuery('#blankbox').html(msg);jQuery('#blankbox').fadeIn("fast");jQuery('#blackout').html('');});
	request.fail(function(jqXHR, textStatus) {alert(textStatus+ "Request failed, try again" );});
}
function getDayDetails(input){
	var request = jQuery.ajax({url: getDayDetailsAjax,type: "POST",data: {invoice: input},cache: false,dataType: 'text'});
	request.done(function(msg) {jQuery('#dayDetailsBox').html(msg);});
	request.fail(function(jqXHR, textStatus) {alert(textStatus+ "Request failed, try again" );});
}
function SaveRental(input, theRow){
	jQuery(theRow).removeClass("savedRental");
	var request = jQuery.ajax({url: saveRentalAjax,type: "POST",data: input,cache: false,dataType: 'text'});
	request.done(function(msg) {jQuery(theRow).addClass("savedRental");jQuery('#responce-box').html(msg);});
	request.fail(function(jqXHR, textStatus) { alert(textStatus+ "Request failed, try again" );});
}

//================================================[charting functions]
function doOccupacityChart(prefix){	
	opts = {
      title: prefix+'ly occupacity', 
      axes:{
        xaxis:{renderer:jQuery.jqplot.DateAxisRenderer}
      }, 
      cursor:{         zoom:true, 
         show: true
      },
		legend: {
            show: true,
            placement: 'inside'
        },
		series:[  
          {linePattern: 'dashed',rendererOptions: {smooth: true},showMarker:true, label: 'ocupacity',}, 
          {showLine:false, label: 'checkout', markerOptions: { size: 9, style:"x" }},
          {showLine:false, label: 'checkin',markerOptions: 	{ size: 13, style:"o" }},
		] 
	};
	var plot2 = jQuery.jqplot(prefix+'chart', chartingData[prefix], opts);
}
