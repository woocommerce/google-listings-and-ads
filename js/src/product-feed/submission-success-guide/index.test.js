jest.mock( '.~/hooks/useAdsCampaigns', () =>
	jest.fn().mockName( 'useAdsCampaigns' )
);

jest.mock( '.~/hooks/useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' ).mockReturnValue( {} )
);

jest.mock( '.~/hooks/useDispatchCoreNotices', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useDispatchCoreNotices' )
		.mockImplementation( () => {
			return {
				createNotice: jest.fn(),
			};
		} ),
} ) );

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import SubmissionSuccessGuide from './index';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import { CAMPAIGN_TYPE_PMAX } from '.~/constants';

const CONNECTED_ADS_ACCOUNT = {
	id: 777777,
	currency: 'PLN',
	symbol: 'zł',
	status: 'connected',
};

const PAID_CAMPAIGN = {
	id: 10,
	name: 'PMax Campaign',
	status: 'enabled',
	type: CAMPAIGN_TYPE_PMAX,
	amount: 20,
	displayCountries: [ 'US' ],
};

const FREE_CAMPAIGN = {
	id: 11,
	name: 'Free Campaign',
	status: 'enabled',
	type: 'shopping',
	amount: 30,
	displayCountries: [ 'US' ],
};

describe( 'SubmissionSuccessGuide', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'Renders the Google Ads credit screen if there are no paid campaigns', () => {
		useGoogleAdsAccount.mockReturnValue( {
			googleAdsAccount: CONNECTED_ADS_ACCOUNT,
			hasFinishedResolution: true,
		} );

		useAdsCampaigns.mockReturnValue( {
			data: [ FREE_CAMPAIGN ],
			loaded: true,
		} );

		render( <SubmissionSuccessGuide /> );

		const button = screen.getByRole( 'button', { name: 'Next' } );
		userEvent.click( button );

		expect(
			screen.getByText( 'Spend $500 to get $500 in Google Ads credits' )
		).toBeInTheDocument();
	} );

	test( 'Renders the enhanced tracking screen if there are paid campaigns', () => {
		useGoogleAdsAccount.mockReturnValue( {
			googleAdsAccount: CONNECTED_ADS_ACCOUNT,
			hasFinishedResolution: true,
		} );

		useAdsCampaigns.mockReturnValue( {
			data: [ PAID_CAMPAIGN ],
			loaded: true,
		} );

		render( <SubmissionSuccessGuide /> );

		const button = screen.getByRole( 'button', { name: 'Next' } );
		userEvent.click( button );

		expect(
			screen.getByText( 'Enhanced Conversion Tracking' )
		).toBeInTheDocument();
	} );
} );
