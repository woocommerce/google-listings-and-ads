/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import usePreference from '~/hooks/usePreference';
import { recordGlaEvent } from '~/utils/tracks';
import {
	ANALYTICS_OVERVIEW_PROMO_CONTEXT,
	ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY,
} from './constants';
import AnalyticsOverviewPromo, { getPromoCopy } from './index';

const REFERRER_QUERY_STRING =
	'referrer_type=analytics_in_product_placements&referrer_id=analytics-overview-promo';

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: jest.fn(),
} ) );

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
	REFERRER_TYPE_ANALYTICS_IN_PRODUCT_PLACEMENTS:
		'analytics_in_product_placements',
} ) );

jest.mock( '@wordpress/components', () => ( {
	Card: ( { children, className } ) => (
		<div className={ className }>{ children }</div>
	),
	CardBody: ( { children } ) => <div>{ children }</div>,
	Flex: ( { children } ) => <div>{ children }</div>,
	FlexBlock: ( { children } ) => <div>{ children }</div>,
	FlexItem: ( { children } ) => <div>{ children }</div>,
} ) );

jest.mock(
	'~/components/app-button',
	() =>
		( { children, href, onClick } ) =>
			href ? (
				<a href={ href } onClick={ onClick }>
					{ children }
				</a>
			) : (
				<button onClick={ onClick }>{ children }</button>
			)
);

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);

jest.mock( '~/hooks/usePreference', () =>
	jest.fn().mockName( 'usePreference' )
);

jest.mock( '~/utils/urls', () => ( {
	getOnboardingUrl: jest.fn( () => '/onboarding' ),
	getSetupAdsUrl: jest.fn( () => '/setup-ads' ),
} ) );

describe( 'AnalyticsOverviewPromo', () => {
	const setPreference = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		useDispatch.mockReturnValue( { set: setPreference } );
		usePreference.mockReturnValue( false );
		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: false,
			hasFinishedResolution: true,
		} );
	} );

	test( 'renders nothing while the connection state is still resolving', () => {
		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: false,
			hasFinishedResolution: false,
		} );

		const { container } = render( <AnalyticsOverviewPromo /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders nothing when the promo has been dismissed', () => {
		usePreference.mockReturnValue( true );

		const { container } = render( <AnalyticsOverviewPromo /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders the not-onboarded copy and a Get started CTA', () => {
		const { container } = render( <AnalyticsOverviewPromo /> );

		expect(
			container.querySelector( '.gla-analytics-overview-promo' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', {
				level: 3,
				name: 'Sales a bit slow? Reach more shoppers with Google.',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Get started' } )
		).toHaveAttribute( 'href', `/onboarding?${ REFERRER_QUERY_STRING }` );
	} );

	test( 'renders the connected copy and a Launch a campaign CTA', () => {
		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: true,
			hasFinishedResolution: true,
		} );

		render( <AnalyticsOverviewPromo /> );

		expect(
			screen.getByRole( 'heading', {
				level: 3,
				name: 'Sales a bit slow? Give your products a boost with Google.',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Launch a campaign' } )
		).toHaveAttribute( 'href', `/setup-ads?${ REFERRER_QUERY_STRING }` );
	} );

	test( 'persists dismissal when the Dismiss button is clicked', () => {
		render( <AnalyticsOverviewPromo /> );

		fireEvent.click( screen.getByRole( 'button', { name: 'Dismiss' } ) );

		expect( setPreference ).toHaveBeenCalledWith(
			PREFERENCES_STORE_NAMESPACE,
			ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY,
			true
		);
	} );

	test( 'fires the view event once when the promo is shown', () => {
		render( <AnalyticsOverviewPromo /> );

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_analytics_in_product_placements_view',
			{
				context: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
				case: 'sales_orders',
			}
		);
	} );

	test( 'does not fire the view event when the promo is not shown', () => {
		usePreference.mockReturnValue( true );

		render( <AnalyticsOverviewPromo /> );

		expect( recordGlaEvent ).not.toHaveBeenCalled();
	} );
} );

describe( 'getPromoCopy', () => {
	test( 'returns null when the case is not recognized', () => {
		expect( getPromoCopy( undefined, false ) ).toBeNull();
		expect( getPromoCopy( 'unknownCase', true ) ).toBeNull();
	} );

	// 'revenue' / 'products' are the literal `metricsCase` values GOOWOO-899's
	// `useProductRevenueMetricsDown()` returns.
	test.each( [
		[
			'revenue',
			false,
			{
				title: 'Sales a bit slow? Reach more shoppers with Google.',
				description:
					'Sync your catalog with Google and grow back your sales by reaching new shoppers right when they are searching to buy.',
				ctaLabel: 'Get started',
				ctaHref: '/onboarding',
			},
		],
		[
			'revenue',
			true,
			{
				title: 'Sales a bit slow? Give your products a boost with Google.',
				description:
					'Launch a Google Ads campaign and grow back your sales by reaching shoppers who are ready to buy.',
				ctaLabel: 'Launch a campaign',
				ctaHref: '/setup-ads',
			},
		],
		[
			'products',
			false,
			{
				title: 'Selling fewer items than usual? Reach more shoppers with Google.',
				description:
					'Sync your catalog with Google and sell more of your products by reaching new shoppers right when they are searching to buy.',
				ctaLabel: 'Get started',
				ctaHref: '/onboarding',
			},
		],
		[
			'products',
			true,
			{
				title: 'Selling fewer items than usual? Give your products a boost with Google.',
				description:
					'Launch a Google Ads campaign and sell more of your products by reaching shoppers who are ready to buy.',
				ctaLabel: 'Launch a campaign',
				ctaHref: '/setup-ads',
			},
		],
	] )( '%s × isConnected=%s', ( matchedCase, isConnected, expected ) => {
		expect( getPromoCopy( matchedCase, isConnected ) ).toEqual( expected );
	} );
} );
