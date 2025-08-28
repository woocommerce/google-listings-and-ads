/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useRecommendedPMaxCampaign from './useRecommendedPMaxCampaign';

jest.mock( '@wordpress/data/src/components/use-select', () => jest.fn() );

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

const mockedRecommendations = [
	{
		id: 111,
		type: 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
		resource_name:
			'customers/{customer_id}/recommendations/{recommendation_id}',
		campaign_id: 111,
		campaign_name: 'Spring Campaign',
		campaign_status: 'ENABLED',
		last_synced: '2024-06-01T12:34:56Z',
	},
	{
		id: 222,
		type: 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
		resource_name:
			'customers/{customer_id}/recommendations/{recommendation_id}',
		campaign_id: 222,
		campaign_name: 'Summer Campaign',
		campaign_status: 'ENABLED',
		last_synced: '2024-06-02T12:34:56Z',
	},
	{
		id: 333,
		type: 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
		resource_name:
			'customers/{customer_id}/recommendations/{recommendation_id}',
		campaign_id: 333,
		campaign_name: 'Winter Campaign',
		campaign_status: 'ENABLED',
		last_synced: '2024-06-03T12:34:56Z',
	},
];

describe( 'usePmaxAssetOptimizationRecommendedCampaign', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'returns null campaign and hasFinishedResolution=true if no recommendations', () => {
		useSelect.mockImplementation( ( cb ) => {
			return cb( () => ( {
				hasFinishedResolution: () => true,
				getAdsRecommendations: () => [],
			} ) );
		} );

		const { result } = renderHook( () => useRecommendedPMaxCampaign() );
		expect( result.current ).toEqual( {
			campaign: null,
			hasFinishedResolution: true,
		} );
	} );

	it( 'returns highest spending enabled PMax campaign with recommendation', () => {
		useSelect.mockImplementation( ( cb ) => {
			return cb( () => ( {
				hasFinishedResolution: () => true,
				getAdsRecommendations: () => mockedRecommendations,
			} ) );
		} );

		const { result } = renderHook( () => useRecommendedPMaxCampaign() );
		expect( result.current ).toEqual( {
			campaign: mockedRecommendations[ 0 ],
			hasFinishedResolution: true,
		} );
	} );
} );
