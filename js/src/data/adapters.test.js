/**
 * Internal dependencies
 */
import {
	adaptAdsBudgetRecommendation,
	adaptAdsBudgetMetrics,
	adaptAssetGroup,
	adaptRaiseAdsBudgetRecommendations,
} from './adapters';
import { ASSET_KEY } from '~/constants';

describe( 'adaptAdsBudgetRecommendation', () => {
	let input;

	beforeEach( () => {
		input = {
			currency: 'USD',
			source: 'google-ads-api',
			daily_budget_baseline: 13,
			recommendations: [
				{
					level: 'Recommended',
					country: 'US',
					daily_budget: 15,
					metrics: {
						cost: 105,
						conversions: 2.2,
						conversions_value: 89.98,
					},
				},
				{
					level: 'High',
					country: 'US',
					daily_budget: 20.5,
					metrics: {
						cost: 143.5,
						conversions: 2.5,
						conversions_value: 98.59,
					},
				},
				{
					level: 'Low',
					country: 'US',
					daily_budget: 7,
					metrics: {
						cost: 49,
						conversions: 2,
						conversions_value: 80.48,
					},
				},
			],
		};
	} );

	const recommended = {
		currency: 'USD',
		country: 'US',
		dailyBudget: 15,
		metrics: {
			cost: 105,
			conversions: 2.2,
			conversionsValue: 89.98,
		},
	};
	const high = {
		currency: 'USD',
		country: 'US',
		dailyBudget: 20.5,
		metrics: {
			cost: 143.5,
			conversions: 2.5,
			conversionsValue: 98.59,
		},
	};
	const low = {
		currency: 'USD',
		country: 'US',
		dailyBudget: 7,
		metrics: {
			cost: 49,
			conversions: 2,
			conversionsValue: 80.48,
		},
	};

	it( 'Adapts the budget recommendation', () => {
		expect( adaptAdsBudgetRecommendation( input ) ).toEqual( {
			recommended,
			high,
			low,
			dailyBudgetBaseline: 13,
			recommendedDailyBudget: 15,
			eventProps: {
				source: 'google-ads-api',
				metrics_availability: 'all',
				recommended_budget: 15,
			},
		} );
	} );

	it( 'Adapts the budget recommendation for the valid levels only', () => {
		const invalidItem = input.recommendations.pop();
		invalidItem.level = 'base';
		input.recommendations.push( invalidItem );

		expect( adaptAdsBudgetRecommendation( input ) ).toEqual( {
			recommended,
			high,
			dailyBudgetBaseline: 13,
			recommendedDailyBudget: 15,
			eventProps: {
				source: 'google-ads-api',
				metrics_availability: 'all',
				recommended_budget: 15,
			},
		} );
	} );

	it( 'Adapts the metrics availability for partially available metrics', () => {
		input.recommendations[ 2 ].metrics = null;
		let eventProps = adaptAdsBudgetRecommendation( input ).eventProps;

		expect( eventProps.metrics_availability ).toEqual( 'partial' );

		input.recommendations.pop();
		eventProps = adaptAdsBudgetRecommendation( input ).eventProps;

		expect( eventProps.metrics_availability ).toEqual( 'all' );

		input.recommendations[ 0 ].metrics = null;
		eventProps = adaptAdsBudgetRecommendation( input ).eventProps;

		expect( eventProps.metrics_availability ).toEqual( 'partial' );

		input.recommendations.pop();
		eventProps = adaptAdsBudgetRecommendation( input ).eventProps;

		expect( eventProps.metrics_availability ).toEqual( 'none' );
	} );

	it( 'Adapts the metrics availability for no available metrics', () => {
		input.recommendations.forEach( ( item ) => {
			item.metrics = null;
		} );
		const { eventProps } = adaptAdsBudgetRecommendation( input );

		expect( eventProps.metrics_availability ).toEqual( 'none' );
	} );

	describe( 'eliminateIdenticalMetrics', () => {
		const eliminatedRecommended = structuredClone( recommended );
		const eliminatedHigh = structuredClone( high );
		const eliminatedLow = structuredClone( low );

		beforeEach( () => {
			const conversions = 3.56;
			const conversionsValue = 71.55541399333742;

			input.recommendations.forEach( ( item ) => {
				item.metrics.conversions = conversions;
				item.metrics.conversions_value = conversionsValue;
			} );

			[ eliminatedRecommended, eliminatedHigh, eliminatedLow ].forEach(
				( item ) => {
					item.metrics.conversions = conversions;
					item.metrics.conversionsValue = conversionsValue;
				}
			);
		} );

		it( 'Should eliminate the budget recommendations to keep the lowest-budget one when conversion-related metrics are identical', () => {
			expect( adaptAdsBudgetRecommendation( input ) ).toEqual( {
				recommended: eliminatedLow,
				dailyBudgetBaseline: 13,
				recommendedDailyBudget: 7,
				eventProps: {
					source: 'google-ads-api',
					metrics_availability: 'all',
					recommended_budget: 7,
				},
			} );

			input.recommendations.pop();

			expect( adaptAdsBudgetRecommendation( input ) ).toEqual( {
				recommended: eliminatedRecommended,
				dailyBudgetBaseline: 13,
				recommendedDailyBudget: 15,
				eventProps: {
					source: 'google-ads-api',
					metrics_availability: 'all',
					recommended_budget: 15,
				},
			} );
		} );

		it( 'Should not eliminate when only some of the recommendations have the same metrics', () => {
			input.recommendations[ 2 ].metrics.conversions = 2.5;

			expect( adaptAdsBudgetRecommendation( input ) ).toEqual( {
				recommended: eliminatedRecommended,
				high: eliminatedHigh,
				low: {
					...eliminatedLow,
					metrics: {
						...eliminatedLow.metrics,
						conversions: 2.5,
					},
				},
				dailyBudgetBaseline: 13,
				recommendedDailyBudget: 15,
				eventProps: {
					source: 'google-ads-api',
					metrics_availability: 'all',
					recommended_budget: 15,
				},
			} );
		} );

		it( 'Should not eliminate if the number of recommendations having metrics is less than 2', () => {
			input.recommendations[ 1 ].metrics = null;
			input.recommendations[ 2 ].metrics = null;

			const expected = {
				recommended: eliminatedRecommended,
				high: {
					...eliminatedHigh,
					metrics: null,
				},
				low: {
					...eliminatedLow,
					metrics: null,
				},
				dailyBudgetBaseline: 13,
				recommendedDailyBudget: 15,
				eventProps: {
					source: 'google-ads-api',
					metrics_availability: 'partial',
					recommended_budget: 15,
				},
			};

			expect( adaptAdsBudgetRecommendation( input ) ).toEqual( expected );

			input.recommendations.pop();

			expect( adaptAdsBudgetRecommendation( input ) ).toEqual( {
				...expected,
				low: undefined,
			} );

			input.recommendations.pop();

			expect( adaptAdsBudgetRecommendation( input ) ).toEqual( {
				...expected,
				high: undefined,
				low: undefined,
				eventProps: {
					...expected.eventProps,
					metrics_availability: 'all',
				},
			} );
		} );
	} );
} );

describe( 'adaptAdsBudgetMetrics', () => {
	it( 'Adapts the budget metrics', () => {
		const input = {
			currency: 'USD',
			country: 'US',
			budget: 15,
			metrics: {
				cost: 105,
				conversions: 2.2,
				conversions_value: 89.98,
			},
		};

		const expected = {
			currency: 'USD',
			country: 'US',
			dailyBudget: 15,
			metrics: {
				cost: 105,
				conversions: 2.2,
				conversionsValue: 89.98,
			},
		};

		expect( adaptAdsBudgetMetrics( input ) ).toEqual( expected );
	} );
} );

describe( 'adaptAssetGroup', () => {
	describe( 'Adapts the order of the multi-value text assets', () => {
		const { HEADLINE, DESCRIPTION } = ASSET_KEY;
		const text10Count = '1234567890';
		const text15Count = '123456789012345';
		const text20Count = text10Count.repeat( 2 );
		const text30Count = text10Count.repeat( 3 );
		const text60Count = text10Count.repeat( 6 );
		const text90Count = text10Count.repeat( 9 );

		const mapContent = ( { content } ) => content;

		let assetGroup;

		beforeEach( () => {
			assetGroup = {
				assets: {
					[ HEADLINE ]: [
						{ content: text15Count },
						{ content: text20Count },
						{ content: text30Count },
					],
					[ DESCRIPTION ]: [
						{ content: text60Count },
						{ content: text90Count },
					],
				},
			};
		} );

		it( 'When the target assets do not exist, it should return the same asset group', () => {
			assetGroup.assets = {};

			expect( adaptAssetGroup( assetGroup ) ).toEqual( assetGroup );
		} );

		it( 'When the first text has a valid character count, it should not change the order', () => {
			expect( adaptAssetGroup( assetGroup ) ).toEqual( assetGroup );
		} );

		it( 'When the first text has an invalid character count but there is no other valid one, it should not change the order', () => {
			assetGroup.assets[ DESCRIPTION ].pop();
			assetGroup.assets[ HEADLINE ] = [
				{ content: text30Count },
				{ content: text20Count },
			];

			expect( adaptAssetGroup( assetGroup ) ).toEqual( assetGroup );
		} );

		it( 'When the first text has an invalid character count, it should move the valid one to the first', () => {
			assetGroup.assets[ DESCRIPTION ].reverse();
			const { assets } = adaptAssetGroup( assetGroup );
			const descriptions = assets[ DESCRIPTION ].map( mapContent );

			expect( descriptions ).toEqual( [ text60Count, text90Count ] );
		} );
	} );
} );

describe( 'adaptRaiseAdsBudgetRecommendations', () => {
	it( 'returns an empty array when the input is not a non-empty array', () => {
		expect( adaptRaiseAdsBudgetRecommendations( null ) ).toEqual( [] );
		expect( adaptRaiseAdsBudgetRecommendations( {} ) ).toEqual( [] );
		expect( adaptRaiseAdsBudgetRecommendations( [] ) ).toEqual( [] );
	} );

	it( 'adapts the raise ads budget recommendations data', () => {
		const input = [
			{
				id: 12345,
				campaign_name: 'Test Campaign',
				details: {
					campaign_budget_recommendation: {
						current_budget_amount: 15,
						recommended_budget_amount: 25,
						budget_options: [
							{
								metrics: {
									cost: '181.971258',
									conversions: 4.828944206237793,
									conversions_value: 622.08,
								},
								budget_amount: '26',
								level: 'Low',
							},
							{
								metrics: {
									cost: '216961447',
									conversions: 5.398608684539795,
									conversions_value: 679.24,
								},
								budget_amount: '31',
								level: 'Recommended',
							},
							{
								metrics: {
									cost: '251946304',
									conversions: 5.776357173919678,
									conversions_value: 731.87,
								},
								budget_amount: '36',
								level: 'High',
							},
						],
					},
				},
			},
		];

		const expected = [
			{
				id: 12345,
				campaignName: 'Test Campaign',
				low: {
					metrics: {
						cost: '181.971258',
						conversions: 4.828944206237793,
						conversionsValue: 622.08,
					},
					budgetAmount: '26',
					dailyBudget: '26',
				},
				recommended: {
					metrics: {
						cost: '216961447',
						conversions: 5.398608684539795,
						conversionsValue: 679.24,
					},
					budgetAmount: '31',
					dailyBudget: '31',
				},
				high: {
					metrics: {
						cost: '251946304',
						conversions: 5.776357173919678,
						conversionsValue: 731.87,
					},
					budgetAmount: '36',
					dailyBudget: '36',
				},
				recommendedDailyBudget: '31',
			},
		];

		expect( adaptRaiseAdsBudgetRecommendations( input ) ).toEqual(
			expected
		);
	} );

	it( 'skips invalid budget options and adapts the valid ones', () => {
		const input = [
			{
				id: 12345,
				campaign_name: 'Test Campaign',
				details: {
					campaign_budget_recommendation: {
						current_budget_amount: 15,
						recommended_budget_amount: 25,
						budget_options: [
							{
								metrics: {
									cost: '181.971258',
									conversions: 4.828944206237793,
									conversions_value: 622.08,
								},
								budget_amount: '26',
								level: 'Low',
							},
							{
								metrics: {
									cost: '216961447',
									conversions: 5.398608684539795,
									conversions_value: 679.24,
								},
								budget_amount: '31',
								level: 'Recommended',
							},
							{
								metrics: {
									cost: '251946304',
									conversions: 5.776357173919678,
									conversions_value: 731.87,
								},
								budget_amount: '36',
								level: 'High',
							},
							{
								metrics: {
									cost: '251946304',
									conversions: 5.776357173919678,
									conversions_value: 731.87,
								},
								budget_amount: '36',
								level: 'Base',
							},
						],
					},
				},
			},
		];

		const expected = [
			{
				id: 12345,
				campaignName: 'Test Campaign',
				low: {
					metrics: {
						cost: '181.971258',
						conversions: 4.828944206237793,
						conversionsValue: 622.08,
					},
					budgetAmount: '26',
					dailyBudget: '26',
				},
				high: {
					metrics: {
						cost: '251946304',
						conversions: 5.776357173919678,
						conversionsValue: 731.87,
					},
					budgetAmount: '36',
					dailyBudget: '36',
				},
				recommended: {
					metrics: {
						cost: '216961447',
						conversions: 5.398608684539795,
						conversionsValue: 679.24,
					},
					budgetAmount: '31',
					dailyBudget: '31',
				},
				recommendedDailyBudget: '31',
			},
		];

		expect( adaptRaiseAdsBudgetRecommendations( input ) ).toEqual(
			expected
		);
	} );

	it( 'returns an empty array when the input is an array of invalid items', () => {
		const input = [
			{
				id: 12345,
				campaign_name: 'Test Campaign',
				details: {
					campaign_budget_recommendation: {
						current_budget_amount: 15,
						recommended_budget_amount: 25,
						budget_options: [],
					},
				},
			},
		];

		expect( adaptRaiseAdsBudgetRecommendations( input ) ).toEqual( [] );
	} );
} );
