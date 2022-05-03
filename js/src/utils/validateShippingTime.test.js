/**
 * Internal dependencies
 */
import validateShippingTime from './validateShippingTime';

const validValues = {
	countryCodes: [ 'US', 'CA' ],
	time: 12,
};

describe( 'validateShippingTime', () => {
	it( 'has no errors properties with validValues', () => {
		const values = {
			...validValues,
		};

		const errors = validateShippingTime( values );

		expect( errors ).toEqual( {} );
	} );

	it( 'has errors.countries when values.countryCodes.length is 0', () => {
		const values = {
			...validValues,
			countryCodes: [],
		};

		const errors = validateShippingTime( values );

		expect( errors ).toHaveProperty( 'countryCodes' );
	} );

	it( 'has errors.time when values.time is empty', () => {
		const values = {
			...validValues,
			time: null,
		};

		const errors = validateShippingTime( values );

		expect( errors ).toHaveProperty( 'time' );
	} );

	it( 'has errors.time when values.time is less than 0', () => {
		const values = {
			...validValues,
			time: -1,
		};

		const errors = validateShippingTime( values );

		expect( errors ).toHaveProperty( 'time' );
	} );
} );
