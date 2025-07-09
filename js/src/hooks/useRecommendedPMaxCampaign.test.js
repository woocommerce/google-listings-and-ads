/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useRecommendedPMaxCampaign from './useRecommendedPMaxCampaign';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';

jest.mock( '@wordpress/data/src/components/use-select', () => jest.fn() );

jest.mock( '~/hooks/useAdsCampaigns', () =>
	jest.fn().mockName( 'useAdsCampaigns' )
);

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

const mockedCampaigns = [
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

const mockedRecommendations = [
	{
		campaign_id: 2,
	},
];

describe( 'usePmaxAssetOptimizationRecommendedCampaign', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'returns null campaign and hasFinishedResolution=false if campaigns not loaded', () => {
		useAdsCampaigns.mockReturnValue( {
			data: mockedCampaigns,
			loaded: true,
		} );
		useSelect.mockImplementation( () => ( {
			campaign: null,
			hasFinishedResolution: false,
		} ) );

		const { result } = renderHook( () => useRecommendedPMaxCampaign() );
		expect( result.current ).toEqual( {
			campaign: null,
			hasFinishedResolution: false,
		} );
	} );

	it( 'returns null campaign and hasFinishedResolution=true if no enabled PMax campaigns', () => {
		useAdsCampaigns.mockReturnValue( {
			data: [
				{
					id: 3,
					name: 'Campaign 3',
					type: 'SEARCH',
					status: 'enabled',
					amount: 300,
				},
			],
			loaded: true,
		} );
		useSelect.mockImplementation( ( cb ) => {
			return cb( () => ( {
				hasFinishedResolution: () => true,
				getAdsRecommendations: () => mockedRecommendations,
			} ) );
		} );

		const { result } = renderHook( () => useRecommendedPMaxCampaign() );
		expect( result.current ).toEqual( {
			campaign: null,
			hasFinishedResolution: true,
		} );
	} );

	it( 'returns null campaign if no recommendations for highest spending campaign', () => {
		useAdsCampaigns.mockReturnValue( {
			data: mockedCampaigns,
			loaded: true,
		} );
		useSelect.mockImplementation( ( cb ) => {
			return cb( () => ( {
				hasFinishedResolution: () => true,
				getAdsRecommendations: () => [ { campaign_id: 4 } ],
			} ) );
		} );

		const { result } = renderHook( () => useRecommendedPMaxCampaign() );
		expect( result.current ).toEqual( {
			campaign: null,
			hasFinishedResolution: true,
		} );
	} );

	it( 'returns null campaign and hasFinishedResolution=false if recommendations are loading', () => {
		useAdsCampaigns.mockReturnValue( {
			data: mockedCampaigns,
			loaded: true,
		} );
		useSelect.mockImplementation( ( cb ) => {
			return cb( () => ( {
				hasFinishedResolution: () => false,
				getAdsRecommendations: () => null,
			} ) );
		} );

		const { result } = renderHook( () => useRecommendedPMaxCampaign() );
		expect( result.current ).toEqual( {
			campaign: null,
			hasFinishedResolution: false,
		} );
	} );

	it( 'returns null campaign if recommendations do not match highest spending campaign', () => {
		useAdsCampaigns.mockReturnValue( {
			data: mockedCampaigns,
			loaded: true,
		} );
		useSelect.mockImplementation( ( cb ) => {
			return cb( () => ( {
				hasFinishedResolution: () => true,
				getAdsRecommendations: () => [ { campaign_id: 1 } ],
			} ) );
		} );

		const { result } = renderHook( () => useRecommendedPMaxCampaign() );
		expect( result.current ).toEqual( {
			campaign: null,
			hasFinishedResolution: true,
		} );
	} );

	it( 'returns highest spending enabled PMax campaign with recommendation', () => {
		useAdsCampaigns.mockReturnValue( {
			data: mockedCampaigns,
			loaded: true,
		} );
		useSelect.mockImplementation( ( cb ) => {
			return cb( () => ( {
				hasFinishedResolution: () => true,
				getAdsRecommendations: () => mockedRecommendations,
			} ) );
		} );

		const { result } = renderHook( () => useRecommendedPMaxCampaign() );
		expect( result.current ).toEqual( {
			campaign: mockedCampaigns[ 1 ],
			hasFinishedResolution: true,
		} );
	} );
} );
