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
		values = {
			amount: 0,
			level: 'custom',
		};
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

	it( 'When the level is not custom, should skip the amount validation', () => {
		const opts = {
			dailyBudget: 100,
			formatAmount: mockFormatAmount,
		};

		expect( validateCampaign( values, opts ) ).toHaveProperty( 'amount' );

		values.level = 'recommended';
		expect( validateCampaign( values, opts ) ).toStrictEqual( {} );

		values.amount = 1;
		expect( validateCampaign( values, opts ) ).toStrictEqual( {} );

		values.level = 'high';
		values.amount = 0;
		expect( validateCampaign( values, opts ) ).toStrictEqual( {} );

		values.amount = 1;
		expect( validateCampaign( values, opts ) ).toStrictEqual( {} );

		values.level = 'low';
		values.amount = 0;
		expect( validateCampaign( values, opts ) ).toStrictEqual( {} );

		values.amount = 1;
		expect( validateCampaign( values, opts ) ).toStrictEqual( {} );

		values.amount = null;
		expect( validateCampaign( values, opts ) ).toStrictEqual( {} );
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

	describe( 'Campaign name', () => {
		beforeEach( () => {
			values = {
				amount: 1,
				level: 'custom',
			};
		} );

		it( 'When the name is over 50 characters, should not pass', () => {
			values.campaignName = 'a'.repeat( 51 );

			const errors = validateCampaign( values, validateCampaignOptions );

			expect( errors ).toHaveProperty( 'campaignName' );
			expect( errors.campaignName ).toMatchSnapshot();
		} );

		it( 'When the name is exactly 50 characters, should pass', () => {
			values.campaignName = 'a'.repeat( 50 );

			const errors = validateCampaign( values, validateCampaignOptions );

			expect( errors ).not.toHaveProperty( 'campaignName' );
		} );

		it( 'When the name is empty, blank or undefined, should pass', () => {
			values.campaignName = '';
			expect(
				validateCampaign( values, validateCampaignOptions )
			).not.toHaveProperty( 'campaignName' );

			values.campaignName = '   ';
			expect(
				validateCampaign( values, validateCampaignOptions )
			).not.toHaveProperty( 'campaignName' );

			values.campaignName = undefined;
			expect(
				validateCampaign( values, validateCampaignOptions )
			).not.toHaveProperty( 'campaignName' );
		} );

		it( 'When the name has surrounding spaces, should count the trimmed value', () => {
			values.campaignName = `  ${ 'a'.repeat( 50 ) }  `;

			const errors = validateCampaign( values, validateCampaignOptions );

			expect( errors ).not.toHaveProperty( 'campaignName' );
		} );

		it( 'When the name has wide characters, should count each of them as 2', () => {
			values.campaignName = '名'.repeat( 25 );
			expect(
				validateCampaign( values, validateCampaignOptions )
			).not.toHaveProperty( 'campaignName' );

			values.campaignName = '名'.repeat( 26 );
			expect(
				validateCampaign( values, validateCampaignOptions )
			).toHaveProperty( 'campaignName' );
		} );

		it( 'When the name is too long and the custom budget is too low, should return both errors', () => {
			values.campaignName = 'a'.repeat( 51 );
			values.amount = 10;

			const errors = validateCampaign( values, {
				dailyBudget: 100,
				formatAmount: mockFormatAmount,
			} );

			expect( errors ).toHaveProperty( 'campaignName' );
			expect( errors ).toHaveProperty( 'amount' );
		} );

		it( 'When the name is too long and the amount is not positive, should return both errors', () => {
			values.campaignName = 'a'.repeat( 51 );
			values.amount = 0;

			const errors = validateCampaign( values, validateCampaignOptions );

			expect( errors ).toHaveProperty( 'campaignName' );
			expect( errors ).toHaveProperty( 'amount' );
		} );

		it( 'When the name is too long and the level is not custom, should still return the name error', () => {
			values.campaignName = 'a'.repeat( 51 );
			values.level = 'recommended';

			const errors = validateCampaign( values, validateCampaignOptions );

			expect( errors ).toStrictEqual( {
				campaignName: expect.any( String ),
			} );
		} );
	} );
} );
