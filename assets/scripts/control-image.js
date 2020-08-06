


jQuery( document ).ready( function() {

	var frame;

	// добавление
	jQuery( '[data-image-role="add"]' ).click( function( e ) {

		var $btn = jQuery( this ),
				$input = jQuery( $btn.attr( 'data-image-input' ) ),
				$thumbnail = jQuery( $input.attr( 'data-image-thumbnail' ) );

		e.preventDefault();

		if( frame ) {
			frame.open();
			return;
		}

		frame = wp.media.frames.questImgAdd = wp.media( {
			states: [
				new wp.media.controller.Library({
					title:    'Рисунок',
					library:  wp.media.query({ type: 'image' } ),
					multiple: false,
				})
			],
			button: {
				text: 'Добавить', // Set the text of the button.
			}
		});

		frame.on( 'select', function() {
			var selected = frame.state().get( 'selection' ).first().toJSON();
			if( selected ){
				$input.val( selected.id );
				$thumbnail.attr( 'src', selected.sizes.thumbnail.url );
			}
		} );

		// открываем
		frame.on( 'open', function() {
			if( $input.val() )
				frame.state().get( 'selection' ).add( wp.media.attachment( $input.val() ) );
		} );

		frame.open();

	} );

	// удаление
	jQuery( '[data-image-role="remove"]' ).click( function( e ) {
		var $btn = jQuery( this ),
				$input = jQuery( $btn.attr( 'data-image-input' ) ),
				$thumbnail = jQuery( $input.attr( 'data-image-thumbnail' ) );
		e.preventDefault();
		console.log( $input.val() );
		if ( $input.val().length > 0 ) {
			$input.val( '' );
			$thumbnail.attr( 'src', $thumbnail.attr( 'data-image-empty' ) );
		}
	} );

} );