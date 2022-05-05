/**
 * Internal dependencies
 */
import validateShippingTimeGroup from './validateShippingTimeGroup';

const validValues = {
	countries: [ 'US', 'CA' ],
	time: 12,
};

describe( 'validateShippingTimeGroup', () => {
	it( 'has no errors properties with validValues', () => {
		const values = {
			...validValues,
		};

		const errors = validateShippingTimeGroup( values );

		expect( errors ).toEqual( {} );
	} );

	it( 'has errors.countries when values.countries.length is 0', () => {
		const values = {
			...validValues,
			countries: [],
		};

		const errors = validateShippingTimeGroup( values );

		expect( errors ).toHaveProperty( 'countries' );
	} );

	it( 'has errors.time when values.time is null', () => {
		const values = {
			...validValues,
			time: null,
		};

		const errors = validateShippingTimeGroup( values );

		expect( errors ).toHaveProperty( 'time' );
	} );

	it( 'has errors.time when values.time is less than 0', () => {
		const values = {
			...validValues,
			time: -1,
		};

		const errors = validateShippingTimeGroup( values );

		expect( errors ).toHaveProperty( 'time' );
	} );
} );
