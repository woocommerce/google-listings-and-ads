/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { act, render } from '@testing-library/react';
import { getQuery } from '@woocommerce/navigation';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import ProductFeed from './';
import localStorage from '~/utils/localStorage';
import isWCTracksEnabled from '~/utils/isWCTracksEnabled';
import { GUIDE_NAMES } from '~/constants';
import { STORE_KEY } from '~/data/constants';
import ProductOverview from './overview';
import ProductFeedTableCard from './product-feed-table-card';
import { useProductFeedHealth } from './sync-health/useSyncHealth';

jest.mock( '@woocommerce/navigation', () => {
	return {
		...jest.requireActual( '@woocommerce/navigation' ),
		getQuery: jest.fn(),
	};
} );

jest.mock( '~/utils/localStorage', () => {
	return {
		get: jest.fn(),
		set: jest.fn(),
	};
} );

jest.mock( '~/utils/isWCTracksEnabled', () => jest.fn() );

jest.mock( '~/components/experience-rating-banner', () =>
	jest.fn().mockName( 'ExperienceRatingBanner' )
);

jest.mock( './overview', () =>
	jest.fn( () => null ).mockName( 'ProductOverview' )
);

jest.mock( './product-feed-table-card', () =>
	jest.fn( () => null ).mockName( 'ProductFeedTableCard' )
);

jest.mock( './sync-health/useSyncHealth', () => ( {
	useProductFeedHealth: jest.fn(),
} ) );

const counts = { total: 3, segments: { approved: 3 } };
const reloadCounts = jest.fn();

const SUBMISSION_SUCCESS_GUIDE_TEXT =
	'You’ve successfully set up Google for WooCommerce! 🎉';
const CES_PROMPT_TEXT = 'How easy was it to set up Google for WooCommerce?';

jest.mock( '~/components/customer-effort-score-prompt', () => () => (
	<div>{ CES_PROMPT_TEXT }</div>
) );

beforeAll( () => {
	// Used in the js/src/hooks/useMenuEffect.js dependency
	window.wpNavMenuClassChange = jest.fn();
} );

describe( 'ProductFeed', () => {
	beforeEach( () => {
		useProductFeedHealth.mockReturnValue( {
			counts,
			reloadCounts,
			isRefreshing: false,
			refresh: jest.fn( () => Promise.resolve() ),
		} );
	} );

	test( 'Gives the overview and the product table the same status counts', () => {
		getQuery.mockReturnValue( {} );
		render( <ProductFeed /> );

		expect( ProductOverview.mock.lastCall[ 0 ].counts ).toBe( counts );
		expect( ProductFeedTableCard.mock.lastCall[ 0 ].counts ).toBe( counts );
	} );

	test( 'Reloads the status counts after channel visibility changed in the product table', () => {
		getQuery.mockReturnValue( {} );
		render( <ProductFeed /> );

		expect(
			ProductFeedTableCard.mock.lastCall[ 0 ].onVisibilityChange
		).toBe( reloadCounts );
	} );

	test( 'Reloads the product table after a refresh from the overview', async () => {
		getQuery.mockReturnValue( {} );
		const invalidate = jest.spyOn(
			dispatch( STORE_KEY ),
			'invalidateResolutionForStoreSelector'
		);
		render( <ProductFeed /> );

		const { onRefresh } = ProductOverview.mock.lastCall[ 0 ];
		await act( () => onRefresh() );

		expect( useProductFeedHealth().refresh ).toHaveBeenCalled();
		expect( invalidate ).toHaveBeenCalledWith( 'getMCProductFeed' );
		invalidate.mockRestore();
	} );

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
} );
