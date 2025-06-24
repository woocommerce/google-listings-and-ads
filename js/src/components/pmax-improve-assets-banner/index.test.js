/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { getHistory } from '@woocommerce/navigation';
import { useDispatch } from '@wordpress/data';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { getEditCampaignUrl } from '~/utils/urls';
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import PMaxImproveAssetsBanner from './index';
import usePreference from '~/hooks/usePreference';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import useAdsRecommendations from '~/hooks/useAdsRecommendations';

jest.mock( '@woocommerce/components', () => ( {
	...jest.requireActual( '@woocommerce/components' ),
	Spinner: jest.fn( () => <div>spinner</div> ),
} ) );

jest.mock( '@wordpress/components', () => ( {
	Notice: ( { children, className } ) => (
		<div className={ className }>{ children }</div>
	),
} ) );

jest.mock( '@woocommerce/navigation', () => ( {
	getHistory: jest.fn(),
} ) );

jest.mock( '~/components/app-button', () => ( { children, onClick } ) => (
	<button onClick={ onClick }>{ children }</button>
) );

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: jest.fn(),
} ) );

jest.mock( '~/hooks/usePreference', () =>
	jest.fn().mockName( 'usePreference' )
);

jest.mock( '~/hooks/useAdsCampaigns', () =>
	jest.fn().mockName( 'useAdsCampaigns' )
);

jest.mock( '~/hooks/useAdsRecommendations', () =>
	jest.fn().mockName( 'useAdsRecommendations' )
);

jest.mock( '~/utils/urls', () => ( {
	getEditCampaignUrl: jest.fn( () => '/edit/2/asset-group' ),
} ) );

const baseCampaigns = [
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

const recommendations = [ { campaign_id: 2, campaign_name: 'Spring sale' } ];

describe( 'PMaxImproveAssetsBanner', () => {
	beforeEach( () => {
		useDispatch.mockReturnValue( { set: () => null } );
	} );

	it( 'renders nothing if expiry is not expired', () => {
		usePreference.mockReturnValue( { expiry: Date.now() + 100000 } );
		useAdsCampaigns.mockReturnValue( { data: baseCampaigns } );
		useAdsRecommendations.mockReturnValue( { recommendations } );
		const { container } = render( <PMaxImproveAssetsBanner /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'resets expiry if expired and renders banner', () => {
		usePreference.mockReturnValue( { expiry: Date.now() - 1000 } );
		useAdsCampaigns.mockReturnValue( { data: baseCampaigns } );
		useAdsRecommendations.mockReturnValue( { recommendations } );
		render( <PMaxImproveAssetsBanner /> );
		expect(
			screen.getByRole( 'button', { name: 'Improve Assets' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Dismiss' } )
		).toBeInTheDocument();
	} );

	it( 'renders nothing if no campaigns data', () => {
		usePreference.mockReturnValue( {} );
		useAdsCampaigns.mockReturnValue( { data: null } );
		useAdsRecommendations.mockReturnValue( { recommendations } );
		const { container } = render( <PMaxImproveAssetsBanner /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders nothing if no recommendations', () => {
		usePreference.mockReturnValue( {} );
		useAdsCampaigns.mockReturnValue( { data: baseCampaigns } );
		useAdsRecommendations.mockReturnValue( { recommendations: [] } );
		const { container } = render( <PMaxImproveAssetsBanner /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders nothing if no enabled PMAX campaigns', () => {
		usePreference.mockReturnValue( {} );
		useAdsCampaigns.mockReturnValue( { data: baseCampaigns } );
		useAdsCampaigns.mockReturnValue( {
			data: [
				{
					id: 3,
					name: 'Campaign 3',
					type: 'shopping',
					status: 'enabled',
					amount: 300,
				},
			],
		} );
		useAdsRecommendations.mockReturnValue( { recommendations } );
		const { container } = render( <PMaxImproveAssetsBanner /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders nothing if no recommendation for highest-spending campaign', () => {
		usePreference.mockReturnValue( {} );
		useAdsCampaigns.mockReturnValue( {
			data: baseCampaigns,
		} );

		useAdsRecommendations.mockReturnValue( {
			recommendations: [
				{ id: 1, campaign_id: 1, campaign_name: 'Spring Campaign' },
			],
		} );
		const { container } = render( <PMaxImproveAssetsBanner /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders banner for highest-spending enabled PMAX campaign with recommendation', () => {
		usePreference.mockReturnValue( {} );
		useAdsCampaigns.mockReturnValue( {
			data: baseCampaigns,
		} );
		useAdsRecommendations.mockReturnValue( { recommendations } );
		render( <PMaxImproveAssetsBanner /> );
		expect(
			screen.getByText(
				/Unlock more sales for your campaign, Campaign 2/
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Improve Assets' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Dismiss' } )
		).toBeInTheDocument();
	} );

	it( 'navigates to edit campaign and sets expiry when Improve Assets is clicked', () => {
		const setMock = jest.fn();
		useDispatch.mockReturnValue( { set: setMock } );

		const historyPush = jest.fn().mockName( 'getHistory().push' );
		getHistory.mockReturnValue( { push: historyPush } );
		usePreference.mockReturnValue( {} );
		useAdsCampaigns.mockReturnValue( { data: baseCampaigns } );
		useAdsRecommendations.mockReturnValue( { recommendations } );

		const MOCK_NOW = 1_700_000_000_000;
		jest.spyOn( Date, 'now' ).mockReturnValue( MOCK_NOW );

		render( <PMaxImproveAssetsBanner /> );
		const improveAssetsButton = screen.getByRole( 'button', {
			name: 'Improve Assets',
		} );

		fireEvent.click( improveAssetsButton );

		const expectedExpiry = MOCK_NOW + 30 * 24 * 60 * 60 * 1000; // 30 days

		expect( setMock ).toHaveBeenCalledWith(
			PREFERENCES_STORE_NAMESPACE,
			'pmax-improve-assets-banner',
			{
				expiry: expectedExpiry,
			}
		);

		expect( setMock ).toHaveBeenCalled();
		expect( getEditCampaignUrl ).toHaveBeenCalledWith( 2, 'asset-group' );
		expect( historyPush ).toHaveBeenCalledWith( '/edit/2/asset-group' );
	} );
} );
