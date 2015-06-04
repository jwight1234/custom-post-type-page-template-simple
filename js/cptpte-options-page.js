(function($) {

	$( "input[type=checkbox]" ).on( "click", function() {

		var template_row = $(this).parent().parent().find('.templates_item').find('select');
		var hidden = template_row.is(":hidden");

		template_row.slideToggle( "fast", function() {

			if(hidden){
				//open
				$(this).find('option').each(function(){

					if ($(this).attr('data-selected') == 'true')
					{
						$(this).attr('selected','selected');
					}

				});

			}else
			{
				//close
				$(this).find('option').each(function(){

					if($(this).is(':selected'))
					{
						$(this).attr('data-selected', 'true');
						$(this).removeAttr('selected');
					}else
					{
						$(this).attr('data-selected', 'false')
					}

				});
			}

		});

	});

	//$('select').select2();

})(jQuery);