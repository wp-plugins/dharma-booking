/*dyamanic variable setup*/
var currentTime = new Date()
var month=new Array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
var today  = currentTime.getDate()+' '+month[currentTime.getMonth()]+' '+currentTime.getFullYear();
var calendarBottom = jQuery('#bottomOfCalendar').offset().left;
var continueButtonPressed = false;
var timeCounter = timerBase;
var updateTime = 0;
jQuery(function () {

	/*countdown timer*/
	if(updateTimeoutOn == 'yes'){		startTimer();	}
	
	/*	start jquery evetnts*/
	jQuery("#startDate").datepicker({dateFormat: "dd M yy", minDate: today});

	if(!jQuery('#formFields #phone.required ').val() && !jQuery('#formFields #fullname.required ').val() && 
		!jQuery('#formFields #email.required ').val()){
			jQuery("#bookFormSubmit, #finalCalendarButton").attr("disabled", true);
	}
	
	jQuery("form#calendarForm #startDate, form#calendarForm #noNights").change(function () { updateMatrix(jQuery("#startDate").val()); });
	
	jQuery("#booking-plugin-blackout").click(function () { 		jQuery('#booking-plugin-blackout, .displaybox').fadeOut('fast'); });

	jQuery('#calenderDiv').on('change', '.rentalSelector',function(){
		if(parseInt(this.options[this.selectedIndex].value)){
			jQuery(this).removeClass('not-selected');
			jQuery(this).addClass('selected');
		}else{
			jQuery(this).removeClass('selected');
			jQuery(this).addClass('not-selected');
		}
  		jQuery('#reviewDiv  table tbody #reviewRow_'+this.id).remove();
		
		var tPrice = jQuery(this).val() * jQuery('#price_'+this.id).data('price')*jQuery('#noNights').val()
		var dPrice = jQuery(this).val() * jQuery('#discount_'+this.id).data('price')*jQuery('#noNights').val()
		var dRow = '';
      if(discountCard != 'none'){dRow = '<td>$<span class="rentalDiscountSubs">'+dPrice+'</span></td>';}
		
		if(jQuery(this).val() > 0){
			var persons = guestString;
			if(jQuery(this).val() > 1)	persons = guestsString;
         
			var newRow=jQuery('<tr id="reviewRow_'+this.id+'">'+
								'<td >'+jQuery('#'+this.id+'-name').html()+'</td>'+	
								'<td><span class="rentalPeopleSubs">'+jQuery(this).val()+'</span> '+persons+'</td>'+
								'<td>$<span class="rentalPriceSubs">'+tPrice+'</span></td>'+dRow+'</tr>');
			jQuery('#reviewDiv  table tbody > tr:last').before(newRow);
		}
		
		var totalPeople =0;
		var totalPrice =0;
		var totalDiscountPrice = 0 ; 
		jQuery('.rentalPeopleSubs').each(function(){totalPeople = totalPeople + parseInt(jQuery(this).html());}); 		
		jQuery('.rentalPriceSubs').each(function(){ totalPrice = totalPrice + parseInt(jQuery(this).html());}); 		
		jQuery('.rentalDiscountSubs').each(function(){totalDiscountPrice = totalDiscountPrice + parseInt(jQuery(this).html());}); 		
		
		var nights = nightString;
		if(jQuery('#noNights').val() > 1)	nights = nightsString;
		jQuery('#reviewTitle').html(jQuery('#noNights').val()+ ' ' + nights +' '+arivingString+' '+jQuery('#nicelyFormatedDate').val());	
		
		var TotalPersons = guestString;
		if(totalPeople > 1)	TotalPersons = guestsString;
		jQuery('#reviewTotal').html(totalPeople+' '+TotalPersons);			
		
		jQuery('#totalPrice').html(totalPrice);
		jQuery('#discountTotal').html(totalDiscountPrice);
		jQuery('#reviewDiv').slideDown('slow');
		
		jQuery('#formFields').animate({opacity:1,width:'100%',fontSize: '100%',height:'100%'},800); 
		jQuery('#formFields').unmask(); 
		if(!continueButtonPressed){
			jQuery('#continue-button').slideDown('slow'); 
		}
	
		  
	});
	
	jQuery('#calenderContainerDiv').on('click', '#continue-button',function(){
		jQuery('#formFields').slideDown('slow'); 
		continueButtonPressed = true;
		jQuery(this).hide("fold");
		//jQuery('html, body').animate({scrollTop:calendarBottom}, 'slow');
		jQuery('#fullname').focus();
	});
	
	jQuery('#calenderDiv').on('mouseenter', '.rentalRow',function(){
		jQuery('#popoutCalendar').fadeIn('fast');
		jQuery('#'+this.id+"-Info").slideDown('slow');
	});
	jQuery('#calenderDiv').on('mouseleave', '.rentalRow',function(){
		jQuery('#popoutCalendar').fadeOut('fast');
		jQuery('#'+this.id+"-Info").slideUp('slow');
	});
	
   jQuery('#formFields .required').change(Validator, function () {
		if(!Validator[this.id]["isValid"](this.value) ){
		   jQuery(this).addClass("errorField");
			jQuery("<li id='error-"+this.id+"'>"+Validator[this.id]["wrong"]+"</li>").appendTo("#errorList");
			jQuery('#errorDiv').slideDown('fast');
			
			jQuery("#finalCalendarButton").attr("disabled", true);
		}else{
			jQuery(this).removeClass("errorField");
			jQuery('#errorList #error-'+this.id).remove();
			if (!jQuery("#errorList li").length ){
				jQuery('#errorDiv').slideUp('fast');
			}
		}
		
		if (!jQuery("#errorList li").length && (jQuery('#formFields #phone.required ').val() && jQuery('#formFields #fullname.required ').val() && jQuery('#formFields #email.required ').val())){
			jQuery("#finalCalendarButton").attr("disabled", false);
			jQuery('.hideme').slideUp();
			jQuery('#finalCalendarButton big').animate({fontSize:'145%'},200); 

		}
   });

	jQuery('#finalCalendarButton').click(function() {

		if(parseInt(jQuery('#totalPrice').html()) > 0 ){
			jQuery("#page").mask(proccessingString);
			submitCalender(jQuery('#calendarForm').serialize()); 
		}else{
			alert(noGuestsAlertString);
		}
  		return false;
	});
});

var Validator = {
	"fullname": {
		"wrong": validnameString,
      "isValid": function (input) { re = /^[a-zA-Z .+]+$/;return re.test(input);   	}
   },
	"phone": {
      "wrong": validphoneString,
      "isValid": function (input) {	var re = /^[0-9()#\/*+\-. ]+$/;	return re.test(input);		}
	},
	"email": {
		"wrong": validemailString,
		"isValid": function (email) {
			var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			return re.test(email);
		}
	}
};
function startTimer(){	
	window.setInterval(function() {
		updateTime = eval(timeCounter)- eval(1);
		jQuery("b[id=time]").html(sec2min(updateTime));
		if(updateTime == 0){
			updateMatrix(jQuery("#startDate").val());
			timeCounter = timerBase; 
			jQuery('#timer').animate({fontSize: '80%',backgroundColor:'#C7FFC7'},1000); //don't like embeded colours &**#
		}else{
			if(updateTime <= timerWarning) { 
				jQuery('html, body').animate({scrollTop:timer}, 'slow');
				jQuery('#timer').animate({fontSize: '140%',backgroundColor:'#FF3C3C'},1200); 
			}
			timeCounter = updateTime;  
		}
	}, 1000);
}
function updateMatrix(theStartDate) { 
	timeCounter = timerBase;
	jQuery('#rentalCalendar').mask(calenderLoadingString);
	jQuery('#continue-button').slideUp('slow'); 

	jQuery('#formFields').animate({opacity:0.57,height:'120px',border:'1px solid black'},900); 
	jQuery('#formFields').mask(formSavedString);
	
	jQuery('#reviewDiv').slideUp('9000');	
	
	jQuery('#pricesText').slideDown('slow');//whats this...
	var request = jQuery.ajax({
		url: refreshPage,
		type: "GET",
		data: {startDate: theStartDate , noNights:  jQuery("#noNights").val() },
		cache: false,
		dataType: 'text'
	});
	request.done(function(msg) {
		jQuery("#calenderDiv").html( msg );
		jQuery("#calenderContainerDiv").unmask();
	//jQuery('#formFields').unmask();
	});
	request.fail(function(jqXHR, textStatus) {
	  alert(requestFailString );
	});
}

function submitCalender(input){
	var request = jQuery.ajax({
		url: ajaxProccess,
		type: "POST",
		data: input,
		cache: false,
		dataType: 'text'
	});
	request.done(function(msg) {
		if( gatewayType == 'none'){	
			jQuery('#paymentGatewayForm #invoice').val(msg);
			jQuery('#makeBookingButton').click();
		}else{
			jQuery("#page").unmask();
			jQuery('#final-details-overview').html('<li>'+jQuery('#fullname').val()+
																	'</li><li>'+jQuery('#phone').val()+
																	'</li><li>'+jQuery('#email').val()+'</li>');
			jQuery('#final-payment-overview').html(jQuery('#reviewDiv').html());
			jQuery('.gatewayInvoiceID').val(msg);
			jQuery('.gatewaydiscription').val(jQuery('#reviewTitle').html());
			jQuery('#gatewaypricefull').val(jQuery('#totalPrice').html());
			if(takeDeposit){
				jQuery('#gatewaypricedeposit').val(jQuery('#totalPrice').html()/takeDeposit);
			}
			jQuery('#booking-plugin-blackout, #gateway-div').fadeIn('fast');
		}
	});
	request.fail(function(jqXHR, textStatus) {
		alert( requestFailString );
		return false;
	});
}

sec2min = function (input) {
    var sec_num = parseInt(input, 10); // don't forget the second parm
    var hours   = Math.floor(sec_num / 3600);
    var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
    var seconds = sec_num - (hours * 3600) - (minutes * 60);

    if (minutes < 10) {minutes = "0"+minutes;}
    if (seconds < 10) {seconds = "0"+seconds;}
    return minutes+':'+seconds;
}
