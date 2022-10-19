/**
 * Internal dependencies
 */
import validateForm from './validateForm';

/**
 * `validateForm` function returns an object, and if any checks are not passed,
 * set properties respectively with an error message to indicate it.
 */
describe( 'validateForm', () => {
	let values;

	beforeEach( () => {
		// Initial values
		values = { countryCodes: [], amount: 0 };
	} );

	it( 'When all checks are passed, should return an empty object', () => {
		const errors = validateForm( {
			countryCodes: [ 'US' ],
			amount: 1,
		} );

		expect( errors ).toStrictEqual( {} );
	} );

	it( 'should indicate multiple unpassed checks by setting properties in the returned object', () => {
		const errors = validateForm( values );

		expect( errors ).toHaveProperty( 'countryCodes' );
		expect( errors ).toHaveProperty( 'amount' );
	} );

	it( 'When the country codes array is empty, should not pass', () => {
		const errors = validateForm( values );

		expect( errors ).toHaveProperty( 'countryCodes' );
		expect( errors.countryCodes ).toMatchSnapshot();
	} );

	it( 'When the amount is not a number, should not pass', () => {
		let errors;

		values.amount = '';
		errors = validateForm( values );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();

		values.amount = undefined;
		errors = validateForm( values );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();

		values.amount = new Date();
		errors = validateForm( values );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();

		values.amount = NaN;
		errors = validateForm( values );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();
	} );

	it( 'When the amount is ≤ 0, should not pass', () => {
		let errors;

		values.amount = 0;
		errors = validateForm( values );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();

		values.amount = -0.01;
		errors = validateForm( values );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();
	} );
} );
