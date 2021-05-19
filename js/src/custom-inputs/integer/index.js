const replaceNonInteger = ( input ) => {
	const regex = new RegExp( '[^0-9]*', 'gi' );
	const value = input.val();
	const newValue = value.replace( regex, '' );

	if ( value !== newValue ) {
		input.val( newValue );
	}
};

const init = ( jQuery ) => {
	const integerInput = jQuery( '.gla-input-integer[type=text]' );
	integerInput.change( function () {
		replaceNonInteger( jQuery( this ) );
	} );
	integerInput.on( 'keyup', function () {
		replaceNonInteger( jQuery( this ) );
	} );
};

export default init;
