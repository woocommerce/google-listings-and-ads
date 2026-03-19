/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, fireEvent, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import { recordGlaEvent } from '~/utils/tracks';
import GoogleAdsPromo from './google-ads-promo';

jest.mock( '~/hooks/useAdsCampaigns', () =>
	jest.fn().mockName( 'useAdsCampaigns' )
);

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

jest.mock( '~/utils/urls', () => ( {
	getGetStartedUrl: jest.fn( () => '/get-started' ),
	getCreateCampaignUrl: jest.fn( () => '/create-campaign' ),
} ) );

describe( 'GoogleAdsPromo Component', () => {
	beforeAll( () => {
		jest.useFakeTimers();
		jest.setSystemTime( new Date( '2025-02-18' ) );
	} );

	afterAll( () => {
		jest.useRealTimers();
	} );

	beforeEach( () => {
		glaData.adsSetupComplete = false;
		jest.clearAllMocks();
	} );

	describe( 'When adsSetupComplete is false', () => {
		test( 'Renders component with setup incomplete messaging when no recent campaigns', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [],
				loading: false,
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
		test( 'Renders component with setup complete messaging when no recent campaigns', () => {
			glaData.adsSetupComplete = true;

			useAdsCampaigns.mockReturnValue( {
				data: [],
				loading: false,
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
			useAdsCampaigns.mockReturnValue( {
				data: [],
				loading: true,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			expect( container.firstChild ).toBeNull();
		} );

		test( 'Does not render when campaigns data is not an array', () => {
			useAdsCampaigns.mockReturnValue( {
				data: null,
				loading: false,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			expect( container.firstChild ).toBeNull();
		} );

		test( 'Does not render when there are recent paid campaigns', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [
					{
						id: 1,
						start_date: '2025-02-16',
						status: 'enabled',
						type: 'performance_max',
					},
				],
				loading: false,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			expect( container.firstChild ).toBeNull();
		} );

		test( 'Does not render when campaign is exactly 14 days ago', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [
					{
						id: 1,
						start_date: '2025-02-04',
						status: 'enabled',
						type: 'performance_max',
					},
				],
				loading: false,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			expect( container.firstChild ).toBeNull();
		} );

		test( 'Renders when there are recent campaigns but no active performance_max ones', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [
					{
						id: 1,
						start_date: '2025-02-16',
						status: 'paused',
						type: 'performance_max',
					},
				],
				loading: false,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			expect( container.firstChild ).toBeInTheDocument();
		} );

		test( 'Renders when campaign is older than 14 days', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [
					{
						id: 1,
						start_date: '2025-01-01',
						status: 'enabled',
						type: 'performance_max',
					},
				],
				loading: false,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			expect( container.firstChild ).toBeInTheDocument();
		} );
	} );

	describe( 'Tracking events', () => {
		test( 'Fires gla_google_ads_promo_shown event when component successfully renders', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [],
				loading: false,
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
			useAdsCampaigns.mockReturnValue( {
				data: [],
				loading: true,
			} );

			render( <GoogleAdsPromo /> );

			expect( recordGlaEvent ).not.toHaveBeenCalled();
		} );

		test( 'Fires tracking event only once when component re-renders with same data', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [],
				loading: false,
			} );

			const { rerender } = render( <GoogleAdsPromo /> );

			expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );

			rerender( <GoogleAdsPromo /> );

			expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		} );

		test( 'Fires gla_google_ads_promo_create_campaign_click event when Create campaign button is clicked', () => {
			glaData.adsSetupComplete = true;

			useAdsCampaigns.mockReturnValue( {
				data: [],
				loading: false,
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

		test( 'Fires wcadmin_gla_google_ads_promo_create_campaign_click event when Get started button is clicked', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [],
				loading: false,
			} );

			render( <GoogleAdsPromo /> );
			fireEvent.click(
				screen.getByRole( 'link', { name: 'Get started' } )
			);

			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'wcadmin_gla_google_ads_promo_create_campaign_click',
				{ context: 'order-attribution-meta-box', href: '/get-started' }
			);
		} );
	} );
} );
