jest.mock( '.~/components/tours/rebranding-tour', () =>
	jest.fn().mockReturnValue( null ).mockName( 'RebrandingTour' )
);

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ProductFeed from './index';
import localStorage from '.~/utils/localStorage';
import isWCTracksEnabled from '.~/utils/isWCTracksEnabled';
import { GUIDE_NAMES } from '.~/constants';
import RebrandingTour from '.~/components/tours/rebranding-tour';

jest.mock( '@woocommerce/navigation', () => {
	return {
		...jest.requireActual( '@woocommerce/navigation' ),
		getQuery: jest.fn(),
	};
} );

jest.mock( '.~/utils/localStorage', () => {
	return {
		get: jest.fn(),
		set: jest.fn(),
	};
} );

jest.mock( '.~/utils/isWCTracksEnabled', () => jest.fn() );

const SUBMISSION_SUCCESS_GUIDE_TEXT =
	'You’ve successfully set up Google for WooCommerce! 🎉';
const CES_PROMPT_TEXT = 'How easy was it to set up Google for WooCommerce?';

jest.mock( '.~/components/customer-effort-score-prompt', () => () => (
	<div>{ CES_PROMPT_TEXT }</div>
) );

beforeAll( () => {
	// Used in the js/src/hooks/useLegacyMenuEffect.js dependency
	window.wpNavMenuClassChange = jest.fn();
} );

describe( 'ProductFeed', () => {
	describe( `When the query string "guide" equals to ${ GUIDE_NAMES.SUBMISSION_SUCCESS }`, () => {
		beforeAll( () => {
			getQuery.mockImplementation( () => {
				return {
					guide: GUIDE_NAMES.SUBMISSION_SUCCESS,
				};
			} );
		} );

		afterAll( () => {
			getQuery.mockReset();
		} );

		test( 'Should render SubmissionSuccessGuide', () => {
			const { queryByText } = render( <ProductFeed /> );
			expect(
				queryByText( SUBMISSION_SUCCESS_GUIDE_TEXT )
			).toBeInTheDocument();
		} );

		test( 'Should not render CustomerEffortScorePrompt', () => {
			const { queryByText } = render( <ProductFeed /> );
			expect( queryByText( CES_PROMPT_TEXT ) ).not.toBeInTheDocument();
		} );
	} );

	describe( `When the query string "guide" does not equals to ${ GUIDE_NAMES.SUBMISSION_SUCCESS }`, () => {
		beforeAll( () => {
			getQuery.mockImplementation( () => {
				return {};
			} );
		} );

		afterAll( () => {
			getQuery.mockReset();
		} );

		test( 'Should not render SubmissionSuccessGuide', () => {
			const { queryByText } = render( <ProductFeed /> );
			expect(
				queryByText( SUBMISSION_SUCCESS_GUIDE_TEXT )
			).not.toBeInTheDocument();
		} );

		describe( 'And both canCESPromptOpenLocal and wcTracksEnabled are false', () => {
			beforeAll( () => {
				localStorage.get.mockImplementation( () => {
					return false;
				} );
				isWCTracksEnabled.mockImplementation( () => {
					return false;
				} );
			} );

			afterAll( () => {
				localStorage.get.mockReset();
				isWCTracksEnabled.mockReset();
			} );

			test( 'Should not render CustomerEffortScorePrompt', () => {
				const { queryByText } = render( <ProductFeed /> );
				expect(
					queryByText( CES_PROMPT_TEXT )
				).not.toBeInTheDocument();
			} );
		} );

		describe( 'And canCESPromptOpenLocal is true but wcTracksEnabled is false', () => {
			beforeAll( () => {
				localStorage.get.mockImplementation( () => {
					return true;
				} );
				isWCTracksEnabled.mockImplementation( () => {
					return false;
				} );
			} );

			afterAll( () => {
				localStorage.get.mockReset();
				isWCTracksEnabled.mockReset();
			} );

			test( 'Should not render CustomerEffortScorePrompt', () => {
				const { queryByText } = render( <ProductFeed /> );
				expect(
					queryByText( CES_PROMPT_TEXT )
				).not.toBeInTheDocument();
			} );
		} );

		describe( 'And wcTracksEnabled is true but canCESPromptOpenLocal is false', () => {
			beforeAll( () => {
				localStorage.get.mockImplementation( () => {
					return false;
				} );
				isWCTracksEnabled.mockImplementation( () => {
					return true;
				} );
			} );

			afterAll( () => {
				localStorage.get.mockReset();
				isWCTracksEnabled.mockReset();
			} );

			test( 'Should not render CustomerEffortScorePrompt', () => {
				const { queryByText } = render( <ProductFeed /> );
				expect(
					queryByText( CES_PROMPT_TEXT )
				).not.toBeInTheDocument();
			} );
		} );

		describe( 'And both canCESPromptOpenLocal and wcTracksEnabled are true', () => {
			beforeAll( () => {
				localStorage.get.mockImplementation( () => {
					return true;
				} );
				isWCTracksEnabled.mockImplementation( () => {
					return true;
				} );
			} );

			afterAll( () => {
				localStorage.get.mockReset();
				isWCTracksEnabled.mockReset();
			} );

			test( 'Should render CustomerEffortScorePrompt', () => {
				const { queryByText } = render( <ProductFeed /> );
				expect( queryByText( CES_PROMPT_TEXT ) ).toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Rebranding Tour', () => {
		test( 'Not rendered in UI', () => {
			RebrandingTour.mockImplementation( () => {
				return null;
			} );

			render( <ProductFeed /> );
			const tour = screen.queryByRole( 'dialog', { name: 'tour' } );
			expect( tour ).not.toBeInTheDocument();
		} );

		test( 'Rendered in UI', () => {
			RebrandingTour.mockImplementation( () => {
				return <div role="dialog" aria-label="tour" />;
			} );

			render( <ProductFeed /> );
			const tour = screen.queryByRole( 'dialog', { name: 'tour' } );
			expect( tour ).toBeInTheDocument();
		} );
	} );
} );
