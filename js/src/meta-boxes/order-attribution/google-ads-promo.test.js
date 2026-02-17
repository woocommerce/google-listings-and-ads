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

// Mock campaign data
const mockRecentCampaign = {
	id: 1,
	start_date: new Date().toISOString().split( 'T' )[ 0 ], // Format: YYYY-MM-DD
	status: 'enabled',
	type: 'performance_max',
};

const mockOldCampaign = {
	id: 1,
	start_date: new Date( '2025-01-01' ).toISOString(),
	status: 'enabled',
	type: 'performance_max',
};

const mockCampaignExact14DaysAgo = {
	id: 1,
	start_date: new Date( Date.now() - 14 * 24 * 60 * 60 * 1000 ) // 14 days ago
		.toISOString()
		.split( 'T' )[ 0 ],
	status: 'enabled',
	type: 'performance_max',
};

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
				data: [ mockRecentCampaign ],
				loading: false,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			expect( container.firstChild ).toBeNull();
		} );

		test( 'Renders when campaign is exactly 14 days ago', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [ mockCampaignExact14DaysAgo ],
				loading: false,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			expect( container.firstChild ).toBeInTheDocument();
		} );

		test( 'Renders when campaign is older than 14 days', () => {
			useAdsCampaigns.mockReturnValue( {
				data: [ mockOldCampaign ],
				loading: false,
			} );

			const { container } = render( <GoogleAdsPromo /> );
			expect( container.firstChild ).toBeInTheDocument();
		} );
	} );
} );
