const replaceNonNumeric = ( input ) => {
	const regex = new RegExp( '[^0-9]*', 'gi' );
	const value = input.val();
	const newValue = value.replace( regex, '' );

	if ( value !== newValue ) {
		input.val( newValue );
	}
};

const init = ( jQuery ) => {
	const numberInput = jQuery( '.gla-input-number[type=text]' );
	numberInput.change( function () {
		replaceNonNumeric( jQuery( this ) );
	} );
	numberInput.on( 'keyup', function () {
		replaceNonNumeric( jQuery( this ) );
	} );
};

export default init;
