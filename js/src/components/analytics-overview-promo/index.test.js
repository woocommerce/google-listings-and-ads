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
import useGoogleAdsAccountReady from '~/hooks/useGoogleAdsAccountReady';
import useHasRecentAdSpend from '~/hooks/useHasRecentAdSpend';
import usePreference from '~/hooks/usePreference';
import useProductRevenueMetricsDown from '~/hooks/useProductRevenueMetricsDown';
import { ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY } from './constants';
import AnalyticsOverviewPromo, { getPromoCopy } from './index';

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: jest.fn(),
} ) );

jest.mock( '@wordpress/preferences', () => ( {
	__esModule: true,
	store: 'preferences',
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

jest.mock( '~/hooks/useGoogleAdsAccountReady', () =>
	jest.fn().mockName( 'useGoogleAdsAccountReady' )
);

jest.mock( '~/hooks/useHasRecentAdSpend', () =>
	jest.fn().mockName( 'useHasRecentAdSpend' )
);

jest.mock( '~/hooks/usePreference', () =>
	jest.fn().mockName( 'usePreference' )
);

jest.mock( '~/hooks/useProductRevenueMetricsDown', () => jest.fn() );

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn( () => ( {
		woocommerce_default_date_range: 'period=month&compare=previous_period',
	} ) ),
} ) );

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
		useGoogleAdsAccountReady.mockReturnValue( {
			isGoogleAdsReady: false,
		} );
		useHasRecentAdSpend.mockReturnValue( {
			hasFinishedResolution: true,
			hasAdSpend: false,
		} );
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: true,
			metricsCase: 'revenue',
		} );
	} );

	test( 'renders nothing while the Google Ads readiness state is still resolving', () => {
		useGoogleAdsAccountReady.mockReturnValue( { isGoogleAdsReady: null } );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders nothing while the recent ad spend state is still resolving', () => {
		useHasRecentAdSpend.mockReturnValue( {
			hasFinishedResolution: false,
			hasAdSpend: false,
		} );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders nothing when there has been recent ad spend', () => {
		useHasRecentAdSpend.mockReturnValue( {
			hasFinishedResolution: true,
			hasAdSpend: true,
		} );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders nothing while the metrics are still resolving', () => {
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: false,
			isDown: false,
			metricsCase: null,
		} );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders nothing when the promo has been dismissed', () => {
		usePreference.mockReturnValue( true );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders nothing when metrics are not trending down', () => {
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: false,
			metricsCase: null,
		} );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders nothing when isDown is true but the metrics case has no copy', () => {
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: true,
			metricsCase: null,
		} );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders the not-onboarded copy and a Get started CTA', () => {
		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

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
		).toHaveAttribute( 'href', '/onboarding' );
	} );

	test( 'renders the connected copy and a Launch a campaign CTA', () => {
		useGoogleAdsAccountReady.mockReturnValue( { isGoogleAdsReady: true } );

		render( <AnalyticsOverviewPromo query={ {} } /> );

		expect(
			screen.getByRole( 'heading', {
				level: 3,
				name: 'Sales a bit slow? Give your products a boost with Google.',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Launch a campaign' } )
		).toHaveAttribute( 'href', '/setup-ads' );
	} );

	test( 'renders the products copy when the products case matched', () => {
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: true,
			metricsCase: 'products',
		} );

		render( <AnalyticsOverviewPromo query={ {} } /> );

		expect(
			screen.getByRole( 'heading', {
				level: 3,
				name: 'Selling fewer items than usual? Reach more shoppers with Google.',
			} )
		).toBeInTheDocument();
	} );

	test( 'persists dismissal when the Dismiss button is clicked', () => {
		render( <AnalyticsOverviewPromo query={ {} } /> );

		fireEvent.click( screen.getByRole( 'button', { name: 'Dismiss' } ) );

		expect( setPreference ).toHaveBeenCalledWith(
			PREFERENCES_STORE_NAMESPACE,
			ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY,
			true
		);
	} );
} );

describe( 'getPromoCopy', () => {
	test( 'returns null when the case is not recognized', () => {
		expect( getPromoCopy( undefined, false ) ).toBeNull();
		expect( getPromoCopy( 'unknownCase', true ) ).toBeNull();
	} );

	// 'revenue' / 'products' are the literal `metricsCase` values
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
