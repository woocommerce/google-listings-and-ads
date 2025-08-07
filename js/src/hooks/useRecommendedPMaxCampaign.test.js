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
		id: 1,
		name: 'Campaign 1',
		type: 'performance_max',
		status: 'enabled',
		amount: 100,
	},
	{
		id: 2,
		name: 'Campaign 2',
		type: 'performance_max',
		status: 'enabled',
		amount: 200,
	},
	{
		id: 3,
		name: 'Campaign 3',
		type: 'SEARCH',
		status: 'enabled',
		amount: 300,
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
			campaign: mockedRecommendations[ 1 ],
			hasFinishedResolution: true,
		} );
	} );
} );
