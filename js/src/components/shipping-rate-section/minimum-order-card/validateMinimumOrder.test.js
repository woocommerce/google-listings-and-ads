/**
 * Internal dependencies
 */
import validateMinimumOrder from './validateMinimumOrder';

const validValues = {
	countries: [ 'US' ],
	currency: 'USD',
	threshold: 50,
};

describe( 'validateMinimumOrder', () => {
	it( 'has no errors properties with validValues', () => {
		const values = {
			...validValues,
		};

		const errors = validateMinimumOrder( values );

		expect( errors ).toEqual( {} );
	} );

	it( 'has errors.countries when values.countries.length is 0', () => {
		const values = {
			...validValues,
			countries: [],
		};

		const errors = validateMinimumOrder( values );

		expect( errors ).toHaveProperty( 'countries' );
	} );

	it( 'has errors.threshold when values.threshold is less than 0', () => {
		const values = {
			...validValues,
			threshold: -1,
		};

		const errors = validateMinimumOrder( values );

		expect( errors ).toHaveProperty( 'threshold' );
	} );

	it( 'has errors.threshold when values.threshold is not entered', () => {
		const values = {
			...validValues,
			threshold: undefined,
		};

		const errors = validateMinimumOrder( values );

		expect( errors ).toHaveProperty( 'threshold' );
	} );
} );
