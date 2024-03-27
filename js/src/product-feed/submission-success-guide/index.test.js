jest.mock( '.~/hooks/useAdsCampaigns', () =>
	jest.fn().mockName( 'useAdsCampaigns' )
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
import { CAMPAIGN_TYPE_PMAX } from '.~/constants';

const PMAX_CAMPAIGN = {
	id: 10,
	name: 'PMax Campaign',
	status: 'enabled',
	type: CAMPAIGN_TYPE_PMAX,
	amount: 20,
	displayCountries: [ 'US' ],
};

describe( 'SubmissionSuccessGuide', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'Renders the Google Ads credit screen if there are no paid campaigns', () => {
		useAdsCampaigns.mockReturnValue( {
			pmaxCampaigns: [],
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
		useAdsCampaigns.mockReturnValue( {
			pmaxCampaigns: [ PMAX_CAMPAIGN ],
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
