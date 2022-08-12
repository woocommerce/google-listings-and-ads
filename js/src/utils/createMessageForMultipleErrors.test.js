/**
 * Internal dependencies
 */
import createMessageForMultipleErrors from './createMessageForMultipleErrors';

describe( 'createMessageForMultipleErrors', () => {
	it( 'No error messages', () => {
		const errors = [];
		const result = createMessageForMultipleErrors( errors );
		expect( result ).toBeNull();
	} );

	it( 'One error message', () => {
		const errors = [ 'Target Audience' ];

		const result = createMessageForMultipleErrors( errors );

		expect( result ).toBe(
			'There is an error in the following action: Target Audience. Other changes have been saved. Please try again later.'
		);
	} );

	it( 'Multiple errors and it is not partially successful', () => {
		const errors = [ 'Promise A', 'Promise B', 'Promise C' ];
		const isPartiallySuccessful = false;

		const result = createMessageForMultipleErrors(
			errors,
			isPartiallySuccessful
		);

		expect( result ).toBe(
			'There are errors in the following actions: Promise A, Promise B and Promise C. Please try again later.'
		);
	} );
} );
