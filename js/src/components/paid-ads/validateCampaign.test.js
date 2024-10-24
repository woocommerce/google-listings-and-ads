/**
 * Internal dependencies
 */
import validateCampaign from './validateCampaign';

const mockFormatAmount = jest
	.fn()
	.mockImplementation( ( amount ) => `Rs ${ amount }` );

/**
 * `validateCampaign` function returns an object, and if any checks are not passed,
 * set properties respectively with an error message to indicate it.
 */
describe( 'validateCampaign', () => {
	let values;
	const validateCampaignOptions = {
		dailyBudget: undefined,
		formatAmount: mockFormatAmount,
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
		values.amount = 10;

		const opts = {
			dailyBudget: 100,
			formatAmount: mockFormatAmount,
		};

		const errors = validateCampaign( values, opts );

		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toContain( 'is at least Rs 30' );
	} );

	it( 'When a budget is provided and the amount is same as the minimum, should pass', () => {
		values.amount = 30;

		const opts = {
			dailyBudget: 100,
			formatAmount: mockFormatAmount,
		};

		const errors = validateCampaign( values, opts );
		expect( errors ).not.toHaveProperty( 'amount' );
	} );

	it( 'When a budget is provided and the amount is greater than the minimum, should pass', () => {
		values.amount = 35;

		const opts = {
			dailyBudget: 100,
			formatAmount: mockFormatAmount,
		};

		const errors = validateCampaign( values, opts );
		expect( errors ).not.toHaveProperty( 'amount' );
	} );

	it( 'The minimum amount should be rounded up to the nearest integer', () => {
		values.amount = 30.99;

		let opts = {
			dailyBudget: 101,
			formatAmount: mockFormatAmount,
		};
		const errors = validateCampaign( values, opts );

		opts = {
			dailyBudget: 102,
			formatAmount: mockFormatAmount,
		};
		const errorsNext = validateCampaign( values, opts );

		expect( errors ).toEqual( errorsNext );
		expect( errors ).toHaveProperty( 'amount' );
		expect( errors.amount ).toContain( 'is at least Rs 31' );
	} );
} );
