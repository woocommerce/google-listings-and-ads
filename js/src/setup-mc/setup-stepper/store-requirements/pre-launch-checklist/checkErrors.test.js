/**
 * Internal dependencies
 */
import checkErrors from './checkErrors';

/**
 * `checkErrors` function returns an object, and if any checks are not passed,
 * set properties respectively with an error message to indicate it.
 */
describe( 'checkErrors', () => {
	describe( 'Should check the presence of required properties in the given object. Returned object should have error messages for respective properties.', () => {
		for ( const property of [
			'website_live',
			'checkout_process_secure',
			'payment_methods_visible',
			'refund_tos_visible',
			'contact_info_visible',
		] ) {
			it( `${ property } === true`, () => {
				expect( checkErrors( {} ) ).toHaveProperty(
					property,
					'Please check the requirement.'
				);

				expect( checkErrors( { [ property ]: 'foo' } ) ).toHaveProperty(
					property,
					'Please check the requirement.'
				);

				expect(
					checkErrors( { [ property ]: true } )
				).not.toHaveProperty( property );
			} );
		}
		it( 'When there are many missing/invalid properties, should report them all.', () => {
			const values = {
				website_live: false,
				payment_methods_visible: 'true',
				refund_tos_visible: [],
				contact_info_visible: {},
			};

			const errorMessage = 'Please check the requirement.';
			expect( checkErrors( values ) ).toEqual( {
				website_live: errorMessage,
				checkout_process_secure: errorMessage,
				payment_methods_visible: errorMessage,
				refund_tos_visible: errorMessage,
				contact_info_visible: errorMessage,
			} );
		} );

		it( 'When all properties are valid, should return an empty object.', () => {
			const values = {
				website_live: true,
				checkout_process_secure: true,
				payment_methods_visible: true,
				refund_tos_visible: true,
				contact_info_visible: true,
			};

			expect( checkErrors( values ) ).toEqual( {} );
		} );
	} );
} );
