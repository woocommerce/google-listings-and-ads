/**
 * Internal dependencies
 */
import checkErrors from './checkErrors';

/**
 * `checkErrors` function returns an object, and if any checks are not passed,
 * set properties respectively with an error message to indicate it.
 */
describe( 'checkErrors', () => {
	const preLaunchFields = [
		'website_live',
		'checkout_process_secure',
		'payment_methods_visible',
		'refund_tos_visible',
		'contact_info_visible',
	];

	let values;

	beforeEach( () => {
		values = {};
		preLaunchFields.forEach( ( field ) => {
			values[ field ] = true;
		} );
	} );

	describe( 'Should check the presence of required properties in the given object. Returned object should have error messages for respective properties.', () => {
		it( 'When all properties are valid, should return an empty object.', () => {
			expect( checkErrors( values ) ).toEqual( {} );
		} );

		it.each( preLaunchFields )(
			'When %s !== true, should have the error message for `preLaunchChecklist` property',
			( field ) => {
				values[ field ] = false;

				expect( checkErrors( values ) ).toHaveProperty(
					'preLaunchChecklist',
					'Please check all requirements.'
				);
			}
		);
	} );
} );
