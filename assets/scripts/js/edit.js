var url_modified = false;

function save_code() {
	$('#form_element').phpr().post('on_save', {
		beforeSend: function(){phprTriggerSave();}, 
		data: { redirect: 0 }, 
		customIndicator: LightLoadingIndicator, 
		error: popupAjaxError,
		update: 'multi'
	}).send();

	return false;
}

jQuery(document).ready(function($) { 
		
	$('html').bindkey('meta+s, ctrl+s', save_code);
	
	var title_field = $('#Blog_Post_title');
	if (title_field && $('#new_record_flag').length) {
		title_field.on('keyup', update_url_title);
		title_field.on('change', update_url_title);
		title_field.on('paste', update_url_title);
	}
	
	if ($('#new_record_flag').length) {
		var url_element = $('#Blog_Post_url_title');
		url_element.on('change', function(){ url_modified=true; });
	}
	
});

function update_url_title() {
	fieldElementValue = jQuery(this).val();
	if (!url_modified)
		jQuery('#Blog_Post_url_title').val(convert_text_to_url(fieldElementValue));
}

