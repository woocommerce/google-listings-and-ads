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
import { PREFERENCES_STORE_NAMESPACE, DAY_IN_SECONDS } from '~/constants';
import RaiseBudgetRecommendationBanner from './index';
import usePreference from '~/hooks/usePreference';
import useRaiseBudgetRecommendations from '~/hooks/useRaiseBudgetRecommendations';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';

jest.mock( '@woocommerce/components', () => ( {
	...jest.requireActual( '@woocommerce/components' ),
	Spinner: jest.fn( () => <div>spinner</div> ),
} ) );

jest.mock( '@wordpress/components', () => ( {
	Notice: ( { children, className } ) => (
		<div className={ className }>{ children }</div>
	),
	Flex: ( { children, className } ) => (
		<div className={ className }>{ children }</div>
	),
	FlexBlock: ( { children, className } ) => (
		<div className={ className }>{ children }</div>
	),
	FlexItem: ( { children, className } ) => (
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

jest.mock( '~/hooks/useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' )
);

jest.mock( '~/hooks/usePreference', () =>
	jest.fn().mockName( 'usePreference' )
);

jest.mock( '~/hooks/useRaiseBudgetRecommendations', () =>
	jest.fn().mockName( 'useRaiseBudgetRecommendations' )
);

jest.mock( '~/utils/urls', () => ( {
	getEditCampaignUrl: jest.fn( () => '/edit/2/asset-group' ),
} ) );

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

const mockedCampaigns = [
	{ campaign_id: 2, campaign_name: 'Campaign 2' },
	{ campaign_id: 3, campaign_name: 'Campaign 3' },
];

describe( 'RaiseBudgetRecommendationBanner', () => {
	beforeEach( () => {
		useDispatch.mockReturnValue( { set: () => null } );
		useGoogleAdsAccount.mockReturnValue( { hasGoogleAdsConnection: true } );
	} );

	it( 'renders nothing if expiry is not expired', () => {
		usePreference.mockReturnValue( {
			actionTime: Date.now() + 100000,
			actionType: 'dismiss',
		} );
		useRaiseBudgetRecommendations.mockReturnValue( {
			campaigns: mockedCampaigns,
			hasFinishedResolution: true,
		} );
		const { container } = render( <RaiseBudgetRecommendationBanner /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders nothing if Google Ads account is not connected', () => {
		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: false,
		} );
		usePreference.mockReturnValue( { expiry: Date.now() + 100000 } );
		useRaiseBudgetRecommendations.mockReturnValue( {
			campaigns: mockedCampaigns,
			hasFinishedResolution: true,
		} );
		const { container } = render( <RaiseBudgetRecommendationBanner /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders banner if expired', () => {
		usePreference.mockReturnValue( {
			actionTime: Date.now() - 30 * DAY_IN_SECONDS * 1000,
			actionType: 'dismiss',
		} );
		useRaiseBudgetRecommendations.mockReturnValue( {
			campaigns: mockedCampaigns,
			hasFinishedResolution: true,
		} );
		render( <RaiseBudgetRecommendationBanner /> );

		expect(
			screen.getByRole( 'button', { name: 'View recommendation' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Dismiss' } )
		).toBeInTheDocument();
	} );

	it( 'renders nothing if no recommended campaigns', () => {
		usePreference.mockReturnValue( {} );
		useRaiseBudgetRecommendations.mockReturnValue( {
			campaigns: [],
			hasFinishedResolution: true,
		} );
		const { container } = render( <RaiseBudgetRecommendationBanner /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'navigates to edit campaign and sets expiry when View Recommendation button is clicked', () => {
		const setMock = jest.fn();
		useDispatch.mockReturnValue( { set: setMock } );

		const historyPush = jest.fn().mockName( 'getHistory().push' );
		getHistory.mockReturnValue( { push: historyPush } );
		usePreference.mockReturnValue( {} );
		useRaiseBudgetRecommendations.mockReturnValue( {
			campaigns: mockedCampaigns,
			hasFinishedResolution: true,
		} );

		const MOCK_NOW = 1_700_000_000_000;
		jest.spyOn( Date, 'now' ).mockReturnValue( MOCK_NOW );

		render( <RaiseBudgetRecommendationBanner /> );
		const viewRecommendationButton = screen.getByRole( 'button', {
			name: 'View recommendation',
		} );

		fireEvent.click( viewRecommendationButton );

		expect( setMock ).toHaveBeenCalledWith(
			PREFERENCES_STORE_NAMESPACE,
			'raise-budget-recommendation-banner',
			{
				actionTime: MOCK_NOW,
				actionType: 'dismiss',
			}
		);

		expect( setMock ).toHaveBeenCalled();
		expect( getEditCampaignUrl ).toHaveBeenCalledWith( 2, 'asset-group' );
		expect( historyPush ).toHaveBeenCalledWith( '/edit/2/asset-group' );
	} );
} );
