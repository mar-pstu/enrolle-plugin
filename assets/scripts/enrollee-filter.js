jQuery( document ).ready( function () {

	var $form = jQuery( 'form#enrollee_filter-form' ),
			$container = jQuery( '#enrollee_filter-result' ),
			$button = jQuery( '#enrollee_filter-submit' ),
			duration = 250;

	if ( typeof enrollee_filter != 'undefined' ) {
		$form.submit( submit );
	}

	function get_values() {
		var query = {};
		jQuery.each( enrollee_filter.fields, function ( index, key ) {
			var $controls = $form.find( '#fieldset-enrollee_filter-'+key ).find( 'input:checked' );
			if ( $controls.length > 0 ) {
				query[ key ] = new Array();
				$controls.each( function () {
					query[ key ][ query[ key ].length ] = jQuery( this ).val();
				} );
			}
		} );
		return query;
	}

	function submit( event ) {
		event.preventDefault();
		var data = {
          'action': 'enrollee_filter',
          'query': JSON.stringify( get_values() ),
        };
		jQuery.ajax( {
			url: enrollee_filter.ajaxurl,
			data: data,
			beforeSend: function( xhr ) {
				$container.slideUp( duration );
				setTimeout( function () {
					$container.empty();
				}, duration );
				$button.val( enrollee_filter.translates.searching ).attr( 'disabled', 'disabled' );
			},
			success: function( data ) {
				$button.val( enrollee_filter.translates.search ).removeAttr( 'disabled' );
				console.log( data );
				if( data && data != '' ) {
					$container.html( data );
					$container.slideDown( duration );
					jQuery( 'body, html' ).animate( {
			      scrollTop: $container.offset().top
			    }, duration * 2 );
				}
			}
		} );
	}

} );