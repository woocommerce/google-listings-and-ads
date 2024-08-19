jest.mock( '.~/components/tours/rebranding-tour', () =>
	jest.fn().mockReturnValue( null ).mockName( 'RebrandingTour' )
);

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import Dashboard from './index';
import isWCTracksEnabled from '.~/utils/isWCTracksEnabled';
import RebrandingTour from '.~/components/tours/rebranding-tour';
import { GUIDE_NAMES } from '.~/constants';

jest.mock( '.~/components/different-currency-notice', () =>
	jest.fn().mockName( 'DifferentCurrencyNotice' )
);

jest.mock( './summary-section', () => jest.fn().mockName( 'SummarySection' ) );

jest.mock( './all-programs-table-card', () =>
	jest.fn().mockName( 'AllProgramsTableCard' )
);

jest.mock( '@woocommerce/settings', () => {
	return {
		getSetting: () => ( {
			code: 'TWD',
		} ),
	};
} );

jest.mock( '@woocommerce/navigation', () => {
	return {
		...jest.requireActual( '@woocommerce/navigation' ),
		getQuery: jest.fn(),
	};
} );

jest.mock( '.~/utils/isWCTracksEnabled', () => jest.fn() );

const CAMPAIGN_CREATION_SUCCESS_GUIDE_TEXT =
	"You've set up a paid Performance Max Campaign!";
const CES_PROMPT_TEXT = 'How easy was it to create a Google Ad campaign?';

jest.mock( '.~/components/customer-effort-score-prompt', () => () => (
	<div>{ CES_PROMPT_TEXT }</div>
) );

beforeAll( () => {
	// Used in the js/src/hooks/useLegacyMenuEffect.js dependency
	window.wpNavMenuClassChange = jest.fn();
} );

describe( 'Dashboard', () => {
	describe( `When the query string "guide" equals to ${ GUIDE_NAMES.CAMPAIGN_CREATION_SUCCESS }`, () => {
		beforeAll( () => {
			getQuery.mockImplementation( () => {
				return {
					guide: GUIDE_NAMES.CAMPAIGN_CREATION_SUCCESS,
				};
			} );
		} );

		afterAll( () => {
			getQuery.mockReset();
		} );

		test( 'Should render CampaignCreationSuccessGuide', () => {
			const { queryByText } = render( <Dashboard /> );
			expect(
				queryByText( CAMPAIGN_CREATION_SUCCESS_GUIDE_TEXT )
			).toBeInTheDocument();
		} );

		describe( 'And wcTracksEnabled is false', () => {
			beforeAll( () => {
				isWCTracksEnabled.mockImplementation( () => {
					return false;
				} );
			} );

			afterAll( () => {
				isWCTracksEnabled.mockReset();
			} );

			test( 'Should not render CustomerEffortScorePrompt when user does not click "Got it"', () => {
				const { queryByText } = render( <Dashboard /> );
				expect(
					queryByText( CES_PROMPT_TEXT )
				).not.toBeInTheDocument();
			} );

			test( 'Should not render CustomerEffortScorePrompt when user clicks "Got it"', async () => {
				const user = userEvent.setup();
				const { queryByText } = render( <Dashboard /> );
				await user.click( screen.getByText( 'Got it' ) );

				expect(
					queryByText( CES_PROMPT_TEXT )
				).not.toBeInTheDocument();
			} );
		} );

		describe( 'And wcTracksEnabled is true', () => {
			beforeAll( () => {
				isWCTracksEnabled.mockImplementation( () => {
					return true;
				} );
			} );

			afterAll( () => {
				isWCTracksEnabled.mockReset();
			} );

			test( 'Should not render CustomerEffortScorePrompt when user does not click "Got it"', () => {
				const { queryByText } = render( <Dashboard /> );
				expect(
					queryByText( CES_PROMPT_TEXT )
				).not.toBeInTheDocument();
			} );

			test( 'Should render CustomerEffortScorePrompt when user clicks "Got it"', async () => {
				const user = userEvent.setup();
				const { queryByText } = render( <Dashboard /> );
				await user.click( screen.getByText( 'Got it' ) );

				expect( queryByText( CES_PROMPT_TEXT ) ).toBeInTheDocument();
			} );
		} );
	} );

	describe( `When the query string "guide" does not equals to ${ GUIDE_NAMES.CAMPAIGN_CREATION_SUCCESS }`, () => {
		beforeAll( () => {
			getQuery.mockImplementation( () => {
				return {};
			} );
		} );

		afterAll( () => {
			getQuery.mockReset();
		} );

		test( 'Should not render CampaignCreationSuccessGuide', () => {
			const { queryByText } = render( <Dashboard /> );
			expect(
				queryByText( CAMPAIGN_CREATION_SUCCESS_GUIDE_TEXT )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'Rebranding Tour', () => {
		beforeAll( () => {
			getQuery.mockImplementation( () => {
				return {};
			} );
		} );

		afterAll( () => {
			getQuery.mockReset();
		} );

		test( 'Not rendered in UI', () => {
			RebrandingTour.mockImplementation( () => {
				return null;
			} );

			render( <Dashboard /> );
			const tour = screen.queryByRole( 'dialog', { name: 'tour' } );
			expect( tour ).not.toBeInTheDocument();
		} );

		test( 'Rendered in UI', () => {
			RebrandingTour.mockImplementation( () => {
				return <div role="dialog" aria-label="tour" />;
			} );

			render( <Dashboard /> );
			const tour = screen.queryByRole( 'dialog', { name: 'tour' } );
			expect( tour ).toBeInTheDocument();
		} );
	} );
} );
