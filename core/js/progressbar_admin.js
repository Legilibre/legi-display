/* =====================================================================================
*
*  Modify the progression
*
*/

function progressBar_modifyProgression(newPercentage,id) {
	id = typeof(id) != 'undefined' ? id : "progressbar";
	jQuery("#"+id+"_image").animate({width: newPercentage+'%'}, 500, function() {  });
}

/* =====================================================================================
*
*  Modify the text
*
*/

function progressBar_modifyText(newText, id) {
	id = typeof(id) != 'undefined' ? id : "progressbar";
	jQuery("#"+id+"_text").html(newText);
}
