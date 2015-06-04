(function($) {

	var template = $('#pageparentdiv').find( '#page_template' );
	
	template.prev().prev().remove();
	template.remove();

})(jQuery);