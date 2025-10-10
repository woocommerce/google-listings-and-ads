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
import useGoogleAdsAccountReady from '~/hooks/useGoogleAdsAccountReady';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';

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

jest.mock( '~/hooks/useGoogleAdsAccountReady', () =>
	jest.fn().mockName( 'useGoogleAdsAccountReady' )
);

jest.mock( '~/hooks/useAdsCampaigns', () =>
	jest.fn().mockName( 'useAdsCampaigns' )
);

jest.mock( '~/hooks/usePreference', () =>
	jest.fn().mockName( 'usePreference' )
);

jest.mock( '~/hooks/useRaiseBudgetRecommendations', () =>
	jest.fn().mockName( 'useRaiseBudgetRecommendations' )
);

jest.mock( '~/hooks/useAdsCurrency', () =>
	jest.fn().mockReturnValue( {
		formatAmount: jest.fn().mockName( 'formatAmount' ),
	} )
);

jest.mock( '~/utils/urls', () => ( {
	getEditCampaignUrl: jest.fn( ( programId ) => `/edit/${ programId }` ),
} ) );

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

const mockedRecommendedCampaigns = [
	{
		id: 1,
		type: 'CAMPAIGN_BUDGET',
		resource_name:
			'customers/{customer_id}/recommendations/{recommendation_id}',
		campaign_id: 1,
		campaign_name: 'Campaign Name',
		campaign_status: 'ENABLED',
		customer_id: 11,
		details: {
			campaign_budget_recommendation: {
				current_budget_amount: 20,
				recommended_budget_amount: 31,
				budget_options: [
					{
						metrics: {
							cost: '139.964209',
							conversions: 4,
							conversions_value: 545.7408447265625,
						},
						budget_amount: '20',
						level: 'current',
					},
					{
						metrics: {
							cost: '181.971258',
							conversions: 4.828944206237793,
							conversions_value: 622.085021972656,
						},
						budget_amount: '26',
						level: 'Low',
					},
					{
						metrics: {
							cost: '216961447',
							conversions: 5.398608684539795,
							conversions_value: 679.2435913085938,
						},
						budget_amount: '31',
						level: 'Recommended',
					},
					{
						metrics: {
							cost: '251946304',
							conversions: 5.776357173919678,
							conversions_value: 731.874328613281,
						},
						budget_amount: '36',
						level: 'High',
					},
				],
			},
		},
		last_synced: '2024-06-01T12:34:56Z',
	},
	{
		id: 2,
		type: 'CAMPAIGN_BUDGET',
		resource_name:
			'customers/{customer_id}/recommendations/{recommendation_id}',
		campaign_id: 2,
		campaign_name: 'Campaign Name 2',
		campaign_status: 'ENABLED',
		customer_id: 22,
		details: {
			campaign_budget_recommendation: {
				current_budget_amount: 10,
				recommended_budget_amount: 21,
				budget_options: [
					{
						metrics: {
							cost: '139.964209',
							conversions: 4,
							conversions_value: 545.7408447265625,
						},
						budget_amount: '20',
						level: 'current',
					},
					{
						metrics: {
							cost: '181.971258',
							conversions: 4.828944206237793,
							conversions_value: 622.085021972656,
						},
						budget_amount: '26',
						level: 'Low',
					},
					{
						metrics: {
							cost: '216961447',
							conversions: 5.398608684539795,
							conversions_value: 679.2435913085938,
						},
						budget_amount: '31',
						level: 'Recommended',
					},
					{
						metrics: {
							cost: '251946304',
							conversions: 5.776357173919678,
							conversions_value: 731.874328613281,
						},
						budget_amount: '36',
						level: 'High',
					},
				],
			},
		},
		last_synced: '2024-06-01T12:34:56Z',
	},
];

const mockedAdsCampaigns = [
	{
		id: 1,
		name: 'Campaign 2025-08-05 16:37:24',
		status: 'enabled',
		type: 'performance_max',
		amount: 11,
		country: 'MU',
		targeted_locations: [ 'MU' ],
	},
	{
		id: 2,
		name: 'Campaign 2025-08-07 13:55:56',
		status: 'enabled',
		type: 'performance_max',
		amount: 159.84,
		country: 'MU',
		targeted_locations: [ 'MU', 'ZW' ],
	},
];

describe( 'RaiseBudgetRecommendationBanner', () => {
	beforeEach( () => {
		useDispatch.mockReturnValue( { set: () => null } );
		useGoogleAdsAccountReady.mockReturnValue( { isGoogleAdsReady: true } );
		useAdsCampaigns.mockReturnValue( { data: mockedAdsCampaigns } );
	} );

	it( 'renders nothing if expiry is not expired', () => {
		usePreference.mockReturnValue( {
			actionTime: Date.now() + 100000,
			actionType: 'dismiss',
		} );
		useRaiseBudgetRecommendations.mockReturnValue( {
			campaigns: mockedRecommendedCampaigns,
			hasFinishedResolution: true,
		} );
		const { container } = render( <RaiseBudgetRecommendationBanner /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders nothing if Google Ads account is not connected', () => {
		useGoogleAdsAccountReady.mockReturnValue( { isGoogleAdsReady: false } );
		usePreference.mockReturnValue( { expiry: Date.now() + 100000 } );
		useRaiseBudgetRecommendations.mockReturnValue( {
			campaigns: mockedRecommendedCampaigns,
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
			campaigns: mockedRecommendedCampaigns,
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

	it( 'renders nothing if no ads campaigns', () => {
		usePreference.mockReturnValue( {} );
		useRaiseBudgetRecommendations.mockReturnValue( {
			campaigns: mockedRecommendedCampaigns,
			hasFinishedResolution: true,
		} );
		useAdsCampaigns.mockReturnValue( { data: [] } );
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
			campaigns: mockedRecommendedCampaigns,
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
		expect( getEditCampaignUrl ).toHaveBeenCalledWith( 1 );
		expect( historyPush ).toHaveBeenCalledWith( '/edit/1' );
	} );
} );
