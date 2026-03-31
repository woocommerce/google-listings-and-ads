/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, fireEvent, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import useHasRecentAdSpend from '~/hooks/useHasRecentAdSpend';
import { recordGlaEvent } from '~/utils/tracks';
import GoogleAdsPromo from './google-ads-promo';

jest.mock( '~/hooks/useHasRecentAdSpend', () =>
	jest.fn().mockName( 'useHasRecentAdSpend' )
);

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

jest.mock( '~/utils/urls', () => ( {
	getGetStartedUrl: jest.fn( () => '/get-started' ),
	getCreateCampaignUrl: jest.fn( () => '/create-campaign' ),
} ) );

describe( 'GoogleAdsPromo Component', () => {
	beforeEach( () => {
		glaData.adsSetupComplete = false;
		jest.clearAllMocks();
	} );

	describe( 'When adsSetupComplete is false', () => {
		test( 'Renders component with setup incomplete messaging when there is no recent ad spend', () => {
			useHasRecentAdSpend.mockReturnValue( {
				loading: false,
				hasFinishedResolution: true,
				hasAdSpend: false,
			} );

			render( <GoogleAdsPromo /> );

			expect(
				screen.getByRole( 'heading', {
					level: 3,
					name: 'Get your products on Google',
				} )
			).toBeInTheDocument();
			expect(
				screen.getByText(
					'Sync your products to reach customers when they’re searching for products like yours across Google'
				)
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'link', { name: 'Get started' } )
			).toBeInTheDocument();
		} );
	} );

	describe( 'When adsSetupComplete is true', () => {
		test( 'Renders component with setup complete messaging when there is no recent ad spend', () => {
			glaData.adsSetupComplete = true;

			useHasRecentAdSpend.mockReturnValue( {
				loading: false,
				hasFinishedResolution: true,
				hasAdSpend: false,
			} );

			render( <GoogleAdsPromo /> );

			expect(
				screen.getByText( 'Get more sales with Google Ads' )
			).toBeInTheDocument();
			expect(
				screen.getByText(
					'Launch a Google Ads campaign and get your products discovered by high-intent shoppers across Google'
				)
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'link', { name: 'Create campaign' } )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Conditional rendering', () => {
		test( 'Does not render when loading', () => {
			useHasRecentAdSpend.mockReturnValue( {
				loading: true,
				hasFinishedResolution: false,
				hasAdSpend: false,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			expect( container.firstChild ).toBeNull();
		} );

		test( 'Does not render when there is recent ad spend', () => {
			useHasRecentAdSpend.mockReturnValue( {
				loading: false,
				hasFinishedResolution: true,
				hasAdSpend: true,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			expect( container.firstChild ).toBeNull();
		} );

		test( 'Renders when there is no recent ad spend', () => {
			useHasRecentAdSpend.mockReturnValue( {
				loading: false,
				hasFinishedResolution: true,
				hasAdSpend: false,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			expect( container.firstChild ).toBeInTheDocument();
		} );
	} );

	describe( 'Tracking events', () => {
		test( 'Fires gla_google_ads_promo_shown event when component successfully renders', () => {
			useHasRecentAdSpend.mockReturnValue( {
				loading: false,
				hasFinishedResolution: true,
				hasAdSpend: false,
			} );

			render( <GoogleAdsPromo /> );

			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_google_ads_promo_shown',
				{
					context: 'order-attribution-meta-box',
				}
			);
			expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		} );

		test( 'Does not fire tracking event when loading', () => {
			useHasRecentAdSpend.mockReturnValue( {
				loading: true,
				hasFinishedResolution: false,
				hasAdSpend: false,
			} );

			render( <GoogleAdsPromo /> );

			expect( recordGlaEvent ).not.toHaveBeenCalled();
		} );

		test( 'Fires tracking event only once when component re-renders with same data', () => {
			useHasRecentAdSpend.mockReturnValue( {
				loading: false,
				hasFinishedResolution: true,
				hasAdSpend: false,
			} );

			const { rerender } = render( <GoogleAdsPromo /> );

			expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );

			rerender( <GoogleAdsPromo /> );

			expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		} );

		test( 'Fires gla_google_ads_promo_create_campaign_click event when Create campaign button is clicked', () => {
			glaData.adsSetupComplete = true;

			useHasRecentAdSpend.mockReturnValue( {
				loading: false,
				hasFinishedResolution: true,
				hasAdSpend: false,
			} );

			render( <GoogleAdsPromo /> );

			fireEvent.click(
				screen.getByRole( 'link', { name: 'Create campaign' } )
			);

			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_google_ads_promo_create_campaign_click',
				{
					context: 'order-attribution-meta-box',
					href: '/create-campaign',
				}
			);
		} );

		test( 'Fires gla_google_ads_promo_get_started_click event when Get started button is clicked', () => {
			useHasRecentAdSpend.mockReturnValue( {
				loading: false,
				hasFinishedResolution: true,
				hasAdSpend: false,
			} );

			render( <GoogleAdsPromo /> );
			fireEvent.click(
				screen.getByRole( 'link', { name: 'Get started' } )
			);

			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_google_ads_promo_get_started_click',
				{ context: 'order-attribution-meta-box', href: '/get-started' }
			);
		} );
	} );
} );
