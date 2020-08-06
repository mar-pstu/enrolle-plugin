( function( $ ) {

  jQuery.fn.checkedall = function() {

  	var $selectAllControl = this,
  		name = $selectAllControl.attr( 'data-name' ),
  		$controls = jQuery( "input[name='" + name + "']" ),
  		checked = $controls.filter( ':checked' ).length;

    function change() {
    	if ( jQuery( $selectAllControl ).prop( 'checked' ) ) {
    		$controls.prop( { 'checked': true } );
	    } else {
	    	$controls.prop( { 'checked': false } );
	    }
    }

    if ( $controls.length == checked ) {
    	$selectAllControl.prop( { 'checked': true } );
    }

    $selectAllControl.on( 'change', change );
    $controls.on( 'change', function () {
    	if ( ! jQuery( this ).prop( 'checked' ) && jQuery( $selectAllControl ).prop( 'checked' ) ) {
    		jQuery( $selectAllControl ).prop( { 'checked': false } );
    	} else {
    		if ( $controls.length == $controls.filter( ':checked' ).length ) {
    			jQuery( $selectAllControl ).prop( { 'checked': true } );
    		}
    	}
    } );

    return this;

  };

} )( jQuery );