/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import GoogleAdsPromo from './google-ads-promo';

// Helpers to create mock campaign data
const createMockRecentCampaign = ( overrides = {} ) => ( {
	id: 1,
	start_date: new Date().toISOString(),
	status: 'enabled',
	type: 'performance_max',
	...overrides,
} );

const createMockOldCampaign = ( overrides = {} ) => ( {
	id: 1,
	start_date: new Date( '2025-01-01' ).toISOString(),
	status: 'enabled',
	type: 'performance_max',
	...overrides,
} );

jest.mock( '~/hooks/useAdsCampaigns', () =>
	jest.fn().mockName( 'useAdsCampaigns' )
);

jest.mock( '~/utils/urls', () => ( {
	getGetStartedUrl: jest.fn( () => '/get-started' ),
	getCreateCampaignUrl: jest.fn( () => '/create-campaign' ),
} ) );

jest.mock( '~/utils/tracks', () => ( {
	addBaseEventProperties: jest.fn( ( props ) => props ),
} ) );

describe( 'GoogleAdsPromo Component', () => {
	beforeEach( () => {
		glaData.adsSetupComplete = false;
		jest.clearAllMocks();
	} );

	describe( 'When adsSetupComplete is false', () => {
		test( 'Renders component with setup incomplete messaging when no recent campaigns', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [ createMockOldCampaign() ],
				loading: false,
			} );

			render( <GoogleAdsPromo /> );

			expect(
				screen.getByText( 'Get your products on Google' )
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
				data: [ createMockOldCampaign() ],
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
				data: [ createMockRecentCampaign() ],
				loading: false,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			expect( container.firstChild ).toBeNull();
		} );

		test( 'Renders when campaign is older than 14 days', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [ createMockOldCampaign() ],
				loading: false,
			} );

			render( <GoogleAdsPromo /> );
			expect(
				screen.getByText( 'Get your products on Google' )
			).toBeInTheDocument();
		} );

		test( 'Renders when campaigns array is empty', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [],
				loading: false,
			} );

			render( <GoogleAdsPromo /> );
			expect(
				screen.getByText( 'Get your products on Google' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Component structure', () => {
		test( 'Renders Google logo', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [],
				loading: false,
			} );

			render( <GoogleAdsPromo /> );

			const logo = screen.getByAltText( 'Google Logo' );
			expect( logo ).toBeInTheDocument();
		} );

		test( 'Has correct CSS class', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [],
				loading: false,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			const promoElement = container.querySelector(
				'.gla-google-ads-promo'
			);
			expect( promoElement ).toBeInTheDocument();
		} );
	} );
} );
