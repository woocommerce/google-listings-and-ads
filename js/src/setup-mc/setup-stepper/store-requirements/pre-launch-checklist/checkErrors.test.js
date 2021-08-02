/**
 * Internal dependencies
 */
import checkErrors from './checkErrors';

/**
 * checkErrors function returns an object, and if any checks are not passed,
 * set properties respectively with an error message to indicate it.
 *
 * To test each cases separately, the meaning of "should (not) pass" in test descriptions are:
 * should pass - returned object should not contain respective property.
 * should not pass - returned object should contain respective property with an error message.
 */
describe( 'checkErrors', () => {
	it( 'When all checks are passed, should return an empty object', () => {
		const values = {
			website_live: true,
			checkout_process_secure: true,
			payment_methods_visible: true,
			refund_tos_visible: true,
			contact_info_visible: true,
		};

		const errors = checkErrors( values );

		expect( errors ).toStrictEqual( {} );
	} );

	it( 'should indicate multiple unpassed checks by setting properties in the returned object', () => {
		const errors = checkErrors( {} );

		expect( errors ).toHaveProperty( 'website_live' );
		expect( errors ).toHaveProperty( 'checkout_process_secure' );
	} );

	describe( 'Requirements', () => {
		it( 'When there are any requirements are not true, should not pass', () => {
			// Not set yet
			let errors = checkErrors( {}, [], [], [] );

			expect( errors ).toHaveProperty( 'website_live' );
			expect( errors.website_live ).toMatchSnapshot();

			expect( errors ).toHaveProperty( 'checkout_process_secure' );
			expect( errors.checkout_process_secure ).toMatchSnapshot();

			expect( errors ).toHaveProperty( 'payment_methods_visible' );
			expect( errors.payment_methods_visible ).toMatchSnapshot();

			expect( errors ).toHaveProperty( 'refund_tos_visible' );
			expect( errors.refund_tos_visible ).toMatchSnapshot();

			expect( errors ).toHaveProperty( 'contact_info_visible' );
			expect( errors.contact_info_visible ).toMatchSnapshot();

			// Invalid value
			const values = {
				website_live: false,
				checkout_process_secure: 1,
				payment_methods_visible: 'true',
				refund_tos_visible: [],
				contact_info_visible: {},
			};

			errors = checkErrors( values, [], [], [] );

			expect( errors ).toHaveProperty( 'website_live' );
			expect( errors.website_live ).toMatchSnapshot();

			expect( errors ).toHaveProperty( 'checkout_process_secure' );
			expect( errors.checkout_process_secure ).toMatchSnapshot();

			expect( errors ).toHaveProperty( 'payment_methods_visible' );
			expect( errors.payment_methods_visible ).toMatchSnapshot();

			expect( errors ).toHaveProperty( 'refund_tos_visible' );
			expect( errors.refund_tos_visible ).toMatchSnapshot();

			expect( errors ).toHaveProperty( 'contact_info_visible' );
			expect( errors.contact_info_visible ).toMatchSnapshot();
		} );

		it( 'When all requirements are true, should pass', () => {
			const values = {
				website_live: true,
				checkout_process_secure: true,
				payment_methods_visible: true,
				refund_tos_visible: true,
				contact_info_visible: true,
			};

			const errors = checkErrors( values, [], [], [] );

			expect( errors ).not.toHaveProperty( 'website_live' );
			expect( errors ).not.toHaveProperty( 'checkout_process_secure' );
			expect( errors ).not.toHaveProperty( 'payment_methods_visible' );
			expect( errors ).not.toHaveProperty( 'refund_tos_visible' );
			expect( errors ).not.toHaveProperty( 'contact_info_visible' );
		} );
	} );
} );
