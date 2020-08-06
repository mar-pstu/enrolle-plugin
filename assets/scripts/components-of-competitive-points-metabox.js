jQuery( document ).ready( function() {


	var $tbody = jQuery( '#components-of-competitive-points-table tbody' ),
		$inside = jQuery( '#pstu_components_of_competitive_points .inside' ),
		$addRowButton = jQuery( '#components-of-competitive-points-add-row' ),
		row = wp.template( 'components-of-competitive-points-row' ),
		checkbox = wp.template( 'checkbox' ),
		$componentsList = jQuery( '#components-list' ),
		$coefficientField = jQuery( '#coefficient-field' ),
		$completeLineEditgButton = jQuery( '#complete-line-editg-button' ),
		editing = false,
		$value = jQuery( '[name=pstu_components_of_competitive_points]' );


	function getRowNumber( element ) {
		return $tbody.find( 'tr' ).index( jQuery( element ).closest( 'tr' ) );
	}


	function update() {
		var compucted = jQuery.extend( {}, AdmissionConditionsComponents );
		$value.val( JSON.stringify( AdmissionConditionsValue) );
		$componentsList.empty();
		$tbody.empty();
		$coefficientField.empty();
		jQuery.each( AdmissionConditionsValue, function( index, value ) {
			$tbody.append( row( Object.assign( value, { number: $tbody.find( 'tr' ).length + 1 } ) ) );
			if ( value.components.length > 0 ) {
				for ( var i = 0; i < value.components.length; i++ ) {
					delete compucted[ value.components[ i ] ];
				}
			}
		} );
		if ( Object.keys( compucted ).length > 0 ) {
			jQuery.each( compucted, function( index, name ) {
				$componentsList.append( checkbox( {
					id: 'test-'+index+'-checkbox',
					class: 'test-item',
					value: index,
					checked: '',
					label: name,
				} ) );
			} );
		}
	}


	function add() {
		AdmissionConditionsValue[ AdmissionConditionsValue.length ] = new Object( {
			coefficient: '',
			components: [],
		} );
		update();
	}


	function remove() {
		AdmissionConditionsValue.splice( getRowNumber( this ), 1 );
		update();
	}


	function edit() {
		var n = getRowNumber( this );
		jQuery.fancybox.open( {
			src: '#edit-row-wrap',
			beforeShow: function() {
				editing = true;
				$coefficientField.val( AdmissionConditionsValue[ n ].coefficient ).attr( 'placeholder', AdmissionConditionsValue[ n ].coefficient );
				jQuery.each( AdmissionConditionsValue[ n ].components, function( index, value ) {
					$componentsList.prepend( checkbox( {
						id: 'test-'+value+'-checkbox',
						class: 'test-item',
						value: value,
						checked: 'checked',
						label: AdmissionConditionsComponents[ value ],
					} ) );
				} );
			},
			afterShow: function() {
				$coefficientField.focus();
			},
			afterClose: function() {
				var result = new Object();
				result.coefficient = $coefficientField.val();
				result.components = [];
				jQuery.each( $componentsList.find( 'input[type=checkbox]:checked' ) , function ( index, value ) {
					result.components[ result.components.length ] = jQuery( value ).val();
				} );
				AdmissionConditionsValue[ n ] = jQuery.extend( {}, result );
				update();
				editing = false;
			}
		} );
	}


	$inside.on( 'click', '#components-of-competitive-points-add-row', add );
	$inside.on( 'click', 'a.remove-row', remove );
	$inside.on( 'click', 'a.edit-row', edit );
	$completeLineEditgButton.on( 'click', function() {
		if ( editing )  {
			jQuery.fancybox.close();
		}
	} )
	$coefficientField.on( 'keypress', function( e ) {
		if ( editing && e.which == 13 ) {
	        jQuery.fancybox.close();
	    }
	} )

	update();



} );