/**
 * Internal dependencies
 */
import createTextForMultipleErrors from './createMessageForMultipleErrors';

describe( 'createTextForMultipleErrors', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'No error messages', () => {
		const errors = [];
		const result = createTextForMultipleErrors( errors );
		expect( result ).toBeNull();
	} );

	it( 'One error message', () => {
		const errors = [ 'Target Audience' ];

		const result = createTextForMultipleErrors( errors );

		expect( result ).toBe(
			'There are errors in the following actions: Target Audience. Other changes have been saved. Please try again later.'
		);
	} );

	it( 'Multiple errors and it is not partially successful', () => {
		const errors = [ 'Promise A', 'Promise B', 'Promise C' ];
		const isPartiallySuccessful = false;

		const result = createTextForMultipleErrors(
			errors,
			isPartiallySuccessful
		);

		expect( result ).toBe(
			'There are errors in the following actions: Promise A, Promise B and Promise C.  Please try again later.'
		);
	} );
} );
