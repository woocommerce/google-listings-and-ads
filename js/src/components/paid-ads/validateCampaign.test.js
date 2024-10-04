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
	const validateCampaignOptions = {
		dailyBudget: undefined,
		formatAmount: jest.mock(),
	};

	beforeEach( () => {
		// Initial values
		values = { amount: 0 };
	} );

	it( 'When all checks are passed, should return an empty object', () => {
		const errors = validateCampaign(
			{
				amount: 1,
			},
			validateCampaignOptions
		);

		expect( errors ).toStrictEqual( {} );
	} );

	it( 'should indicate multiple unpassed checks by setting properties in the returned object', () => {
		const errors = validateCampaign( values, validateCampaignOptions );

		expect( errors ).toHaveProperty( 'amount' );
	} );

	it( 'When the amount is not a number, should not pass', () => {
		let errors;

		values.amount = '';
		errors = validateCampaign( values, validateCampaignOptions );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();

		values.amount = undefined;
		errors = validateCampaign( values, validateCampaignOptions );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();

		values.amount = new Date();
		errors = validateCampaign( values, validateCampaignOptions );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();

		values.amount = NaN;
		errors = validateCampaign( values, validateCampaignOptions );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();
	} );

	it( 'When the amount is ≤ 0, should not pass', () => {
		let errors;

		values.amount = 0;
		errors = validateCampaign( values, validateCampaignOptions );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();

		values.amount = -0.01;
		errors = validateCampaign( values, validateCampaignOptions );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toMatchSnapshot();
	} );

	it( 'When a budget is provided and the amount is less than the minimum, should not pass', () => {
		const mockFormatAmount = jest.fn().mockReturnValue( 'Rs 30' );
		values.amount = 10;

		const opts = {
			dailyBudget: 100,
			formatAmount: mockFormatAmount,
		};

		const errors = validateCampaign( values, opts );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toContain( 'is at least Rs 30' );
	} );

	it( 'When a budget is provided and the amount is same than the minimum, should pass', () => {
		const mockFormatAmount = jest.fn().mockReturnValue( 'Rs 30' );
		values.amount = 30;

		const opts = {
			dailyBudget: 100,
			formatAmount: mockFormatAmount,
		};

		const errors = validateCampaign( values, opts );
		expect( errors ).not.toHaveProperty( 'amount' );
	} );

	it( 'When a budget is provided and the amount is greater than the minimum, should pass', () => {
		const mockFormatAmount = jest.fn().mockReturnValue( 'Rs 35' );
		values.amount = 35;

		const opts = {
			dailyBudget: 100,
			formatAmount: mockFormatAmount,
		};

		const errors = validateCampaign( values, opts );
		expect( errors ).not.toHaveProperty( 'amount' );
	} );
} );
