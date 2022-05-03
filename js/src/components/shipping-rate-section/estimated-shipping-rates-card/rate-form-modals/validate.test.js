/**
 * Internal dependencies
 */
import validate from './validate';

const validValues = {
	countries: [ 'US' ],
	rate: 0,
};

describe( 'validate', () => {
	it( 'has no errors properties with validValues', () => {
		const values = {
			...validValues,
		};

		const errors = validate( values );

		expect( errors ).toEqual( {} );
	} );

	it( 'has errors.countries when values.countries.length is 0', () => {
		const values = {
			...validValues,
			countries: [],
		};

		const errors = validate( values );

		expect( errors ).toHaveProperty( 'countries' );
	} );

	it( 'has errors.rate when values.rate is empty', () => {
		const values = {
			...validValues,
			rate: undefined,
		};

		const errors = validate( values );

		expect( errors ).toHaveProperty( 'rate' );
	} );

	it( 'has errors.rate when values.rate is less than 0', () => {
		const values = {
			...validValues,
			rate: -1,
		};

		const errors = validate( values );

		expect( errors ).toHaveProperty( 'rate' );
	} );
} );
