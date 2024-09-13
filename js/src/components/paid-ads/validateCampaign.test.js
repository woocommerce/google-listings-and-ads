/**
 * Internal dependencies
 */
import validateCampaign from './validateCampaign';

/**
 * `validateCampaign` function returns an object, and if any checks are not passed,
 * set properties respectively with an error message to indicate it.
 */
describe( 'validateCampaign', () => {
	let values;

	beforeEach( () => {
		// Initial values
		values = { amount: 0 };
	} );

	it( 'When all checks are passed, should return an empty object', () => {
		const errors = validateCampaign( {
			amount: 1,
		} );

		expect( errors ).toStrictEqual( {} );
	} );

	it( 'should indicate multiple unpassed checks by setting properties in the returned object', () => {
		const errors = validateCampaign( values );

		expect( errors ).toHaveProperty( 'amount' );
	} );

	it( 'When the amount is not a number, should not pass', () => {
		let errors;

		values.amount = '';
		errors = validateCampaign( values );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();

		values.amount = undefined;
		errors = validateCampaign( values );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();

		values.amount = new Date();
		errors = validateCampaign( values );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();

		values.amount = NaN;
		errors = validateCampaign( values );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();
	} );

	it( 'When the amount is ≤ 0, should not pass', () => {
		let errors;

		values.amount = 0;
		errors = validateCampaign( values );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();

		values.amount = -0.01;
		errors = validateCampaign( values );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();
	} );

	it( 'When a budget is provided and the amount is less than the minimum, should not pass', () => {
		values.amount = 10;

		const opts = {
			budget: {
				daily_budget: 100,
			},
			budgetMin: 0.3,
		};

		const errors = validateCampaign( values, opts );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toContain( 'at least 30' );
	} );

	it( 'When a budget is provided and the amount is greater than the minimum, should pass', () => {
		values.amount = 35;

		const opts = {
			budget: {
				daily_budget: 100,
			},
			budgetMin: 0.3,
		};

		const errors = validateCampaign( values );
		expect( errors ).not.toHaveProperty( 'amount' );
	} );
} );
