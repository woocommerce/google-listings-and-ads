/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useRaiseBudgetRecommendations from './useRaiseBudgetRecommendations';
import useGoogleAdsAccount from './useGoogleAdsAccount';

jest.mock( '@wordpress/data/src/components/use-select', () => jest.fn() );

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

jest.mock( './useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' )
);

const mockedRecommendations = [
	{
		id: 111,
		type: 'CAMPAIGN_BUDGET',
		resource_name:
			'customers/{customer_id}/recommendations/{recommendation_id}',
		campaign_id: 111,
		campaign_name: 'Spring Campaign',
		campaign_status: 'ENABLED',
		details: {
			campaign_budget_recommendations: {
				current_budget_amount_micros: '5000000',
				recommended_budget_amount_micros: '8000000',
				budget_options: [
					{
						budget_amount_micros: 1000000,
						impact: {
							weekly_cost_micros: '42000000',
							weekly_conversions: 120,
							weekly_conversions_value_micros: '240000000',
						},
					},
				],
			},
		},
		last_synced: '2024-06-01T12:34:56Z',
	},
	{
		id: 222,
		type: 'MARGINAL_ROI_CAMPAIGN_BUDGET',
		resource_name:
			'customers/{customer_id}/recommendations/{recommendation_id}',
		campaign_id: 222,
		campaign_name: 'Summer Campaign',
		campaign_status: 'ENABLED',
		details: {
			campaign_budget_recommendations: {
				current_budget_amount_micros: '4000000',
				recommended_budget_amount_micros: '5000000',
				budget_options: [
					{
						budget_amount_micros: 5000000,
						impact: {
							weekly_cost_micros: '12000000',
							weekly_conversions: 20,
							weekly_conversions_value_micros: '240000000',
						},
					},
				],
			},
		},
		last_synced: '2024-06-02T12:34:56Z',
	},
	{
		id: 333,
		type: 'MARGINAL_ROI_CAMPAIGN_BUDGET',
		resource_name:
			'customers/{customer_id}/recommendations/{recommendation_id}',
		campaign_id: 333,
		campaign_name: 'Winter Campaign',
		campaign_status: 'ENABLED',
		details: {
			campaign_budget_recommendations: {
				current_budget_amount_micros: '9000000',
				recommended_budget_amount_micros: '15000000',
				budget_options: [
					{
						budget_amount_micros: 8000000,
						impact: {
							weekly_cost_micros: '4000000',
							weekly_conversions: 230,
							weekly_conversions_value_micros: '890000000',
						},
					},
				],
			},
		},
		last_synced: '2024-06-03T12:34:56Z',
	},
];

describe( 'useRaiseBudgetRecommendations', () => {
	beforeEach( () => {
		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: true,
			hasFinishedResolution: true,
		} );
	} );

	it( 'returns empty campaigns and resolution status when there is no connected Google Ads account', () => {
		useSelect.mockImplementation( ( cb ) => {
			return cb( () => ( {
				hasFinishedResolution: () => true,
				getAdsRecommendations: () => [],
			} ) );
		} );
		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: false,
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useRaiseBudgetRecommendations() );

		expect( result.current ).toEqual( {
			campaigns: [],
			hasFinishedResolution: true,
		} );
	} );

	it( 'returns empty campaigns and resolution status when no recommendations', () => {
		useSelect.mockImplementation( ( cb ) => {
			return cb( () => ( {
				hasFinishedResolution: () => true,
				getAdsRecommendations: () => [],
			} ) );
		} );

		const { result } = renderHook( () => useRaiseBudgetRecommendations() );
		expect( result.current ).toEqual( {
			campaigns: [],
			hasFinishedResolution: true,
		} );
	} );

	it( 'returns campaigns and hasFinishedResolution true when recommendations exist', () => {
		useSelect.mockImplementation( ( cb ) => {
			return cb( () => ( {
				hasFinishedResolution: () => true,
				getAdsRecommendations: () => mockedRecommendations,
			} ) );
		} );

		const { result } = renderHook( () => useRaiseBudgetRecommendations() );
		expect( result.current ).toEqual( {
			campaigns: mockedRecommendations,
			hasFinishedResolution: true,
		} );
	} );
} );
