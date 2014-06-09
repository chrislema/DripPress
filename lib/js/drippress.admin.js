//*********************************************************************************************
// now start the engine
//*********************************************************************************************
jQuery(document).ready( function($) {


//*********************************************************************************************
//  hide fields for all day
//*********************************************************************************************

	$( 'div#dppress-schd' ).each(function() {

		// first check on load
		if ( $( this ).find( 'input#dppress-live' ).prop( 'checked' ) ) {
			$( this ).find( 'ul.dppress-data' ).show();
		} else {
			$( this ).find( 'ul.dppress-data' ).hide();
		}

		// now look for change
		$( this ).find( 'input#dppress-live' ).change(function() {
			// hide if checked
			if ( $( this ).prop('checked' ) ) {
				$( 'ul.dppress-data' ).slideDown( 'slow' );
			} else {
				$( 'ul.dppress-data' ).slideUp( 'slow' );
			}
		});

	});

//*********************************************************************************************
// that's all folks. we're done here
//*********************************************************************************************

});
