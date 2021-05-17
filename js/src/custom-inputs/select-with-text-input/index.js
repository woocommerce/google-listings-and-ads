const toggleCustomInput = ( selectInput ) => {
	const customInput = selectInput
		.parents( 'div.select-with-text-input' )
		.find( '.custom-input' );
	if ( selectInput.val() === '_gla_custom_value' ) {
		customInput.show();
	} else {
		customInput.hide();
	}
};

const init = ( jQuery ) => {
	const selectWithTextInputBoxes = jQuery(
		'div.select-with-text-input select'
	);
	selectWithTextInputBoxes.each( function ( i, input ) {
		toggleCustomInput( jQuery( input ) );
	} );
	selectWithTextInputBoxes.change( function () {
		toggleCustomInput( jQuery( this ) );
	} );
};

export default init;
