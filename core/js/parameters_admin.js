/* =====================================================================================
*
*  Toggle folder
*
*/

function activateDeactivate_Params(param, toChange) {
	isChecked = jQuery("#"+param).is(':checked');
	for (i=0; i<toChange.length; i++) {
		if (!isChecked) {
			if (toChange[i].substring(0, 1)!="!") {
				jQuery("label[for='"+toChange[i]+"']").parents("tr").eq(0).hide() ; 
				jQuery("#"+toChange[i]).attr('disabled', 'disabled') ; 
				jQuery("#"+toChange[i]+"_workaround").attr('disabled', 'disabled') ; 
			} else {
				jQuery("label[for='"+toChange[i].substring(1)+"']").parents("tr").eq(0).show() ; 
				jQuery("#"+toChange[i].substring(1)).removeAttr('disabled') ;
				jQuery("#"+toChange[i].substring(1)+"_workaround").removeAttr('disabled') ;
			}
		} else {
			if (toChange[i].substring(0, 1)!="!") {
				jQuery("label[for='"+toChange[i]+"']").parents("tr").eq(0).show() ; 
				jQuery("#"+toChange[i]).removeAttr('disabled') ;
				jQuery("#"+toChange[i]+"_workaround").removeAttr('disabled') ;
			} else {
				jQuery("label[for='"+toChange[i].substring(1)+"']").parents("tr").eq(0).hide() ; 
				jQuery("#"+toChange[i].substring(1)).attr('disabled', 'disabled') ; 
				jQuery("#"+toChange[i].substring(1)+"_workaround").attr('disabled', 'disabled') ; 
			}
		}
	}
	return isChecked ; 
}

/* =====================================================================================
*
*  Remove param
*
*/

function del_param(param, md5, pluginID) {

	jQuery("#wait_"+md5).show();
		
	var arguments = {
		action: 'del_param', 
		pluginID: pluginID, 
		param : param
	} 
	
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		if (response=="ok") {
			document.location = document.location ; 
		}
	}).error(function(x,e) { 
		if (x.status==0){
			//Offline
		} else if (x.status==500){
			remove_param(param) ; 
		} 
	});    
}

/* =====================================================================================
*
*  Add param
*
*/

function add_param(param, md5, pluginID) {

	jQuery("#wait_"+md5).show();
		
	var arguments = {
		action: 'add_param', 
		pluginID: pluginID, 
		param : param
	} 
	
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		if (response=="ok") {
			document.location = document.location ; 
		}
	}).error(function(x,e) { 
		if (x.status==0){
			//Offline
		} else if (x.status==500){
			remove_param(param) ; 
		} 
	});    
}

/* =====================================================================================
*
*  Pour ajouter un media
*
*/

var paramMediaReturn = "" ; 

jQuery(document).ready(function() {
 
	window.send_to_editor = function(html) {
	    imgurl = jQuery('img',html).attr('src');
	    jQuery('#'+paramMediaReturn).val(imgurl);
	    tb_remove();
	}
 
});