(function($) {

	// we create a copy of the WP inline edit post function
	var wp_inline_edit = inlineEditPost.edit;
	var bulk_row = $( '#bulk-edit' );

	if (bulk_row.find( 'select[name="page_template"]' )) {
		bulk_row.find( 'select[name="page_template"]' ).parent().hide();
	};

	// and then we overwrite the function with our own code
	inlineEditPost.edit = function( id ) {

		// "call" the original WP edit function
		// we don't want to leave WordPress hanging
		wp_inline_edit.apply( this, arguments );

		// get the post ID
		var post_id = 0;
		if ( typeof( id ) == 'object' ){
			post_id = parseInt( this.getId( id ) );
		}

		if ( post_id > 0 ) {

			// define the edit row
			var edit_row = $( '#edit-' + post_id );

			// get the template name
			var _wp_page_template = $( '#wp_page_template-' + post_id ).attr('data-slug');

			// set the film rating
			edit_row.find( 'select[name="_wp_page_template"]' ).val( _wp_page_template );

			if (edit_row.find( 'select[name="page_template"]' )) {
				edit_row.find( 'select[name="page_template"]' ).parent().hide();
			};

		}

	};



	$( '#bulk_edit' ).on( 'click', function() {

		// define the bulk edit row
		var bulk_row = $( '#bulk-edit' );

		// get the selected post ids that are being edited
		var post_ids = new Array();
		
		bulk_row.find( '#bulk-titles' ).children().each( function() {
			post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
		});

		var _wp_page_template = bulk_row.find( 'select[name="_wp_page_template"]' ).val();

		// save the data
		$.ajax({
			url: ajaxurl, // this is a variable that WordPress has already defined for us
			type: 'POST',
			async: false,
			cache: false,
			data: {
				action: 'cptpts_save_bulk_quick_edit', // this is the name of our WP AJAX function that we'll set up next
				post_ids: post_ids, // and these are the 2 parameters we're passing to our function
				_wp_page_template: _wp_page_template
			}
		});

	});

})(jQuery);