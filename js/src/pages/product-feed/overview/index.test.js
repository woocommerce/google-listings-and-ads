/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import {
	act,
	fireEvent,
	render,
	screen,
	waitFor,
	within,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import ProductOverview from './';
import { useProductFeedHealth } from '../sync-health/useSyncHealth';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import { recordGlaEvent } from '~/utils/tracks';
import { STORE_KEY } from '~/data/constants';

jest.mock( '@wordpress/api-fetch', () => {
	const mock = jest.fn();
	// The data store registers a middleware when it loads.
	mock.use = jest.fn();
	return mock;
} );

jest.mock( '~/hooks/useAppSelectDispatch', () =>
	jest.fn( () => ( {
		hasFinishedResolution: true,
		data: { status: 'APPROVED' },
	} ) )
);

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

jest.mock( '../sync-health/technical-details', () =>
	jest.fn( ( { onRequestClose } ) => (
		<div role="dialog" aria-label="Advanced troubleshooting">
			<button onClick={ onRequestClose }>Close</button>
		</div>
	) )
);

const now = Math.floor( Date.now() / 1000 );

const buildSummary = ( overrides = {} ) => ( {
	generated_at: now,
	state: 'synced',
	syncing: { products: 0, full_sync: false, shipping: false },
	reasons: [],
	pillars: {
		connection: { status: 'ok' },
		products: {
			status: 'ok',
			last_push_at: now - 300,
			full_sync: {
				in_progress: false,
				interrupted: false,
				last_completed_at: now - 86400,
			},
		},
		shipping: {
			status: 'ok',
			syncable: true,
			last_success_at: now - 7200,
			last_failure: null,
		},
		review: { status: 'ok', data_at: now - 600, loading: false },
	},
	queue: {},
	...overrides,
} );

const buildJourney = ( segments = {} ) => {
	const all = {
		approved: 70,
		pending_sync: 3,
		pending_review: 5,
		disapproved: 12,
		not_synced: 10,
		...segments,
	};

	return {
		total: Object.values( all ).reduce( ( sum, count ) => sum + count, 0 ),
		segments: all,
	};
};

/**
 * A fetch Response stand-in, since the hooks call `apiFetch` with `parse: false`.
 *
 * @param {Object} body JSON body.
 * @return {Object} Response-like object.
 */
const asResponse = ( body ) => ( {
	json: () => Promise.resolve( body ),
	clone() {
		return this;
	},
} );

const mockApi = ( {
	summary = buildSummary(),
	journey = buildJourney(),
	reviewRefresh = Promise.resolve(
		asResponse( { started: true, reason: 'started', available_at: null } )
	),
} = {} ) => {
	apiFetch.mockImplementation( ( { path } ) => {
		if ( path.includes( 'review-refresh' ) ) {
			return reviewRefresh;
		}
		return Promise.resolve(
			asResponse( path.includes( 'product-journey' ) ? journey : summary )
		);
	} );
};

const callsTo = ( pattern ) =>
	apiFetch.mock.calls.filter( ( [ { path } ] ) => pattern.test( path ) );

/**
 * Renders the overview with its data hook, as the Product Feed page does.
 */
const Harness = () => {
	const health = useProductFeedHealth();

	return (
		<ProductOverview
			summary={ health.summary }
			counts={ health.counts }
			error={ health.error }
			isRefreshing={ health.isRefreshing }
			refreshNote={ health.refreshNote }
			onRefresh={ health.refresh }
		/>
	);
};

const getHeader = async () =>
	within(
		( await screen.findByRole( 'heading', { name: 'Overview' } ) )
			.parentElement
	);

const legendLabels = () =>
	screen.queryAllByText( /^\d+% / ).map( ( item ) => item.textContent );

/**
 * Finds the innermost element whose whole text is `text`, for copy that mixes
 * plain text with elements such as `<time>`.
 *
 * @param {string} text
 * @return {HTMLElement} The element.
 */
const getByTextContent = ( text ) =>
	screen.getByText(
		( _, element ) =>
			element.textContent === text &&
			! Array.from( element.children ).some(
				( child ) => child.textContent === text
			)
	);

/**
 * When a kind of sync last ran, from its label in the sync times list.
 *
 * @param {string} label
 * @return {string} The value next to the label.
 */
const syncTime = ( label ) =>
	screen.getByText( label, { selector: 'dt' } ).nextElementSibling
		.textContent;

describe( 'ProductOverview', () => {
	afterEach( () => {
		apiFetch.mockReset();
		recordGlaEvent.mockClear();
		useAppSelectDispatch.mockClear();
	} );

	it( 'refreshes once on page visit: asks for review results, then re-checks status and counts', async () => {
		mockApi();
		render( <Harness /> );

		expect(
			await screen.findByText( '70 of 100 products approved by Google' )
		).toBeInTheDocument();

		expect( callsTo( /review-refresh/ ) ).toHaveLength( 1 );
		expect( callsTo( /review-refresh/ )[ 0 ][ 0 ].method ).toBe( 'POST' );
		expect( callsTo( /sync-health\?refresh=1$/ ) ).toHaveLength( 1 );
		expect( callsTo( /product-journey/ ) ).toHaveLength( 1 );

		// The automatic refresh is not a click.
		expect( recordGlaEvent ).not.toHaveBeenCalled();
	} );

	it( 'shows the sync badge, when sync status was checked, and when each kind of sync last ran', async () => {
		mockApi();
		render( <Harness /> );

		const header = await getHeader();
		expect( await header.findByText( 'Synced' ) ).toBeInTheDocument();
		expect(
			header.getByText( 'All changes have been sent to Google' )
		).toBeInTheDocument();
		expect(
			getByTextContent( 'Checked a few seconds ago' )
		).toBeInTheDocument();

		expect( syncTime( 'Last full catalog sync' ) ).toBe( 'a day ago' );
		expect( syncTime( 'Last product sync' ) ).toBe( '5 minutes ago' );
		expect( syncTime( 'Last shipping sync' ) ).toBe( '2 hours ago' );
	} );

	it( 'leaves out the shipping sync time when shipping is managed in Merchant Center', async () => {
		const summary = buildSummary();
		summary.pillars.shipping.syncable = false;
		mockApi( { summary } );
		render( <Harness /> );

		expect(
			await screen.findByText( 'Last product sync' )
		).toBeInTheDocument();
		expect(
			screen.queryByText( 'Last shipping sync' )
		).not.toBeInTheDocument();
	} );

	it.each( [
		[
			{ products: 3, full_sync: false, shipping: true },
			'Sending 3 product changes to Google',
		],
		[
			{ products: 1, full_sync: false, shipping: false },
			'Sending 1 product change to Google',
		],
		[
			{ products: 40, full_sync: true, shipping: false },
			'Sending your full catalog to Google',
		],
		[
			{ products: 0, full_sync: false, shipping: true },
			'Sending shipping data to Google',
		],
	] )(
		'describes what is being sent while syncing: %o',
		async ( syncing, description ) => {
			mockApi( {
				summary: buildSummary( { state: 'syncing', syncing } ),
			} );
			render( <Harness /> );

			const header = await getHeader();
			expect( await header.findByText( 'Syncing' ) ).toBeInTheDocument();
			expect( header.getByText( description ) ).toBeInTheDocument();
		}
	);

	it( 'explains a failed sync with the first non-review reason that stops data reaching Google', async () => {
		mockApi( {
			summary: buildSummary( {
				state: 'failed',
				reasons: [
					// Review results never explain a failed sync.
					{
						code: 'review_refresh_failed',
						pillar: 'review',
						severity: 'error',
						data: {},
					},
					{
						code: 'products_backoff',
						pillar: 'products',
						severity: 'warning',
						data: { count: 2 },
					},
					{
						code: 'product_job_paused',
						pillar: 'products',
						severity: 'paused',
						data: {},
					},
				],
			} ),
		} );
		render( <Harness /> );

		const header = await getHeader();
		expect( await header.findByText( 'Sync failed' ) ).toBeInTheDocument();
		expect(
			header.getByText(
				'Product sync stopped after failing several times in a row.'
			)
		).toBeInTheDocument();
	} );

	it.each( [
		[ { data_at: now - 600, loading: false }, 'Updated 10 minutes ago' ],
		[ { data_at: null, loading: false }, 'Not checked yet' ],
		[
			{ data_at: now - 600, loading: true },
			'Updating… (last updated 10 minutes ago)',
		],
		[ { data_at: null, loading: true }, 'Updating…' ],
	] )(
		'says how old the review results are next to the headline: %o',
		async ( review, text ) => {
			const summary = buildSummary();
			summary.pillars.review = { status: 'ok', ...review };
			mockApi( { summary } );
			render( <Harness /> );
			await screen.findByText( 'Synced' );

			expect( getByTextContent( text ) ).toBeInTheDocument();
		}
	);

	it( 'shows products by status in the bar, Approved first, then statuses that have products', async () => {
		mockApi( { journey: buildJourney( { pending_sync: 0 } ) } );
		render( <Harness /> );
		await screen.findByText( '70 of 97 products approved by Google' );

		expect( legendLabels() ).toEqual( [
			'72% Approved',
			'5% Pending review',
			'12% Disapproved',
			'10% Not synced',
		] );
	} );

	it( 'always shows Approved in the bar, even when no products are approved', async () => {
		mockApi( {
			journey: buildJourney( {
				approved: 0,
				pending_sync: 0,
				pending_review: 0,
				disapproved: 4,
				not_synced: 0,
			} ),
		} );
		render( <Harness /> );
		await screen.findByText( '0 of 4 products approved by Google' );

		expect( legendLabels() ).toEqual( [
			'0% Approved',
			'100% Disapproved',
		] );
	} );

	it( 'shows no bar when there are no products', async () => {
		mockApi( {
			journey: buildJourney( {
				approved: 0,
				pending_sync: 0,
				pending_review: 0,
				disapproved: 0,
				not_synced: 0,
			} ),
		} );
		render( <Harness /> );

		expect(
			await screen.findByText( '0 of 0 products approved by Google' )
		).toBeInTheDocument();
		expect( legendLabels() ).toEqual( [] );
		expect( screen.queryByText( /NaN/ ) ).not.toBeInTheDocument();
	} );

	it( 'says no products can show while the account is disapproved, linking to the account issues', async () => {
		useAppSelectDispatch.mockReturnValue( {
			hasFinishedResolution: true,
			data: { status: 'DISAPPROVED' },
		} );
		const accountIssues = document.createElement( 'div' );
		accountIssues.id = 'gla-account-issues';
		accountIssues.scrollIntoView = jest.fn();
		document.body.appendChild( accountIssues );

		try {
			mockApi();
			render( <Harness /> );

			expect(
				await screen.findByText(
					'No products can show on Google until your Merchant Center account issues are fixed.'
				)
			).toBeInTheDocument();

			await userEvent.click(
				screen.getByRole( 'button', { name: 'View account issues' } )
			);
			expect( accountIssues.scrollIntoView ).toHaveBeenCalled();
		} finally {
			accountIssues.remove();
			useAppSelectDispatch.mockReturnValue( {
				hasFinishedResolution: true,
				data: { status: 'APPROVED' },
			} );
		}
	} );

	it( 'does not warn about the account when it is approved', async () => {
		mockApi();
		render( <Harness /> );
		await screen.findByText( '70 of 100 products approved by Google' );

		expect(
			screen.queryByRole( 'button', { name: 'View account issues' } )
		).not.toBeInTheDocument();
	} );

	it( 'refreshes again on click, recording the click, and is never disabled', async () => {
		mockApi();
		render( <Harness /> );
		await screen.findByText( '70 of 100 products approved by Google' );

		const button = screen.getByRole( 'button', { name: 'Refresh' } );
		await userEvent.click( button );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_product_feed_refresh_click'
		);
		await waitFor( () =>
			expect( callsTo( /sync-health\?refresh=1$/ ) ).toHaveLength( 2 )
		);
		expect( callsTo( /review-refresh/ ) ).toHaveLength( 2 );
		expect( callsTo( /product-journey/ ) ).toHaveLength( 2 );
		expect( button ).toBeEnabled();
	} );

	it( 'keeps the latest refresh when an earlier one finishes last', async () => {
		let finishFirstSummary;
		let summaryCalls = 0;
		apiFetch.mockImplementation( ( { path } ) => {
			if ( path.includes( 'review-refresh' ) ) {
				return Promise.resolve( asResponse( { started: true } ) );
			}
			if ( path.includes( 'product-journey' ) ) {
				return Promise.resolve( asResponse( buildJourney() ) );
			}

			summaryCalls++;
			if ( summaryCalls === 1 ) {
				// The automatic refresh's summary arrives last, and is stale.
				return new Promise( ( resolve ) => {
					finishFirstSummary = () =>
						resolve(
							asResponse( buildSummary( { state: 'failed' } ) )
						);
				} );
			}
			return Promise.resolve( asResponse( buildSummary() ) );
		} );
		render( <Harness /> );
		await waitFor( () => expect( finishFirstSummary ).toBeDefined() );

		const button = screen.getByRole( 'button', { name: 'Refresh' } );
		await userEvent.click( button );
		expect( await screen.findByText( 'Synced' ) ).toBeInTheDocument();
		expect( button ).not.toHaveClass( 'is-busy' );

		await act( async () => finishFirstSummary() );

		expect( screen.getByText( 'Synced' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Sync failed' ) ).not.toBeInTheDocument();
		expect( button ).not.toHaveClass( 'is-busy' );
	} );

	it.each( [
		[
			'running',
			{ started: false, reason: 'running', available_at: null },
			'Review results are already being fetched from Google.',
		],
		[
			'recent',
			{ started: false, reason: 'recent', available_at: now + 180 },
			'Review results were fetched recently. New results can be fetched in 3 minutes.',
		],
	] )(
		'explains when review results were not fetched, only after a Refresh click: %s',
		async ( _, result, note ) => {
			mockApi( {
				reviewRefresh: Promise.resolve( asResponse( result ) ),
			} );
			render( <Harness /> );
			await screen.findByText( '70 of 100 products approved by Google' );

			// Not after the automatic refresh on page visit.
			expect( screen.getByRole( 'status' ) ).toBeEmptyDOMElement();

			await userEvent.click(
				screen.getByRole( 'button', { name: 'Refresh' } )
			);

			expect( await screen.findByRole( 'status' ) ).toHaveTextContent(
				note
			);
		}
	);

	it( 'still re-checks sync status and counts when review results cannot be requested', async () => {
		mockApi( {
			reviewRefresh: Promise.reject(
				asResponse( { code: 'error', message: 'Server error' } )
			),
		} );
		render( <Harness /> );

		expect(
			await screen.findByText( '70 of 100 products approved by Google' )
		).toBeInTheDocument();
		expect( callsTo( /sync-health\?refresh=1$/ ) ).toHaveLength( 1 );
		expect( callsTo( /product-journey/ ) ).toHaveLength( 1 );
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Could not fetch review results from Google.'
		);
	} );

	it( 'opens Advanced troubleshooting from View diagnostics, recording the click', async () => {
		mockApi();
		render( <Harness /> );
		await screen.findByText( '70 of 100 products approved by Google' );

		expect(
			screen.queryByRole( 'dialog', { name: 'Advanced troubleshooting' } )
		).not.toBeInTheDocument();

		await userEvent.click(
			screen.getByRole( 'button', { name: 'View diagnostics' } )
		);

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_product_feed_diagnostics_click'
		);
		expect(
			screen.getByRole( 'dialog', { name: 'Advanced troubleshooting' } )
		).toBeInTheDocument();

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Close' } )
		);
		expect(
			screen.queryByRole( 'dialog', { name: 'Advanced troubleshooting' } )
		).not.toBeInTheDocument();
	} );

	describe( 'while review results are being fetched from Google', () => {
		const loading = ( overrides = {} ) => {
			const summary = buildSummary( overrides );
			summary.pillars.review.loading = true;
			return summary;
		};

		/**
		 * Answers each sync-health request with the next of `summaries` (the
		 * last one repeats), and each product-journey request likewise.
		 *
		 * @param {Array<Object>} summaries
		 * @param {Array<Object>} [journeys]
		 */
		const mockSequence = ( summaries, journeys = [ buildJourney() ] ) => {
			const next = ( list, index ) =>
				list[ Math.min( index, list.length - 1 ) ];
			let summaryIndex = 0;
			let journeyIndex = 0;
			apiFetch.mockImplementation( ( { path } ) => {
				if ( path.includes( 'review-refresh' ) ) {
					return Promise.resolve( asResponse( { started: true } ) );
				}
				if ( path.includes( 'product-journey' ) ) {
					return Promise.resolve(
						asResponse( next( journeys, journeyIndex++ ) )
					);
				}
				return Promise.resolve(
					asResponse( next( summaries, summaryIndex++ ) )
				);
			} );
		};

		const advance = async ( ms ) => {
			await act( async () => {
				jest.advanceTimersByTime( ms );
			} );
			// Let the responses, which take a few promise hops, settle.
			await act( () => Promise.resolve() );
		};

		let invalidate;
		beforeEach( () => {
			jest.useFakeTimers();
			invalidate = jest.spyOn(
				dispatch( STORE_KEY ),
				'invalidateResolutionForStoreSelector'
			);
		} );

		afterEach( () => {
			invalidate.mockRestore();
			jest.useRealTimers();
		} );

		it( 're-checks the cached sync status every 30 seconds until they are in, then reloads the counts and the table', async () => {
			mockSequence(
				[ loading(), loading(), buildSummary() ],
				[ buildJourney(), buildJourney( { approved: 80 } ) ]
			);
			render( <Harness /> );
			await screen.findByText( '70 of 100 products approved by Google' );
			expect( callsTo( /sync-health\?refresh=1$/ ) ).toHaveLength( 1 );

			// Re-checks use the server's cached summary, not `refresh=1`.
			await advance( 30 * 1000 );
			expect( callsTo( /sync-health$/ ) ).toHaveLength( 1 );
			expect( invalidate ).not.toHaveBeenCalled();

			await advance( 30 * 1000 );
			expect( callsTo( /sync-health$/ ) ).toHaveLength( 2 );
			expect(
				await screen.findByText(
					'80 of 110 products approved by Google'
				)
			).toBeInTheDocument();
			expect( callsTo( /product-journey/ ) ).toHaveLength( 2 );
			expect( invalidate ).toHaveBeenCalledWith( 'getMCProductFeed' );

			// Done: nothing more on a timer, and Google isn't asked again.
			await advance( 5 * 60 * 1000 );
			expect( callsTo( /sync-health$/ ) ).toHaveLength( 2 );
			expect( callsTo( /sync-health\?refresh=1$/ ) ).toHaveLength( 1 );
			expect( callsTo( /review-refresh/ ) ).toHaveLength( 1 );
		} );

		it.each( [ 'stalled', 'stuck', 'paused' ] )(
			'does not wait for a review job that is %s',
			async ( jobState ) => {
				const summary = loading();
				summary.pillars.review.job_state = jobState;
				mockSequence( [ summary ] );
				render( <Harness /> );
				await screen.findByText(
					'70 of 100 products approved by Google'
				);

				await advance( 5 * 60 * 1000 );

				expect( callsTo( /sync-health$/ ) ).toHaveLength( 0 );
			}
		);

		it( 'stops after 40 checks', async () => {
			mockSequence( [ loading() ] );
			render( <Harness /> );
			await screen.findByText( '70 of 100 products approved by Google' );

			for ( let check = 0; check < 45; check++ ) {
				await advance( 30 * 1000 );
			}

			expect( callsTo( /sync-health$/ ) ).toHaveLength( 40 );
		} );

		it( 'stops after 3 failed checks in a row, and shows the error', async () => {
			apiFetch.mockImplementation( ( { path } ) => {
				if ( path.includes( 'review-refresh' ) ) {
					return Promise.resolve( asResponse( { started: true } ) );
				}
				if ( path.includes( 'product-journey' ) ) {
					return Promise.resolve( asResponse( buildJourney() ) );
				}
				if ( path.endsWith( 'sync-health' ) ) {
					return Promise.reject(
						asResponse( { message: 'Server error' } )
					);
				}
				return Promise.resolve( asResponse( loading() ) );
			} );
			const { container } = render( <Harness /> );
			await screen.findByText( '70 of 100 products approved by Google' );

			// Scoped to the card: the notice is also announced in a live region.
			const card = within( container );
			for ( let check = 0; check < 3; check++ ) {
				expect(
					card.queryByText( 'Server error' )
				).not.toBeInTheDocument();
				await advance( 30 * 1000 );
			}
			expect( card.getByText( 'Server error' ) ).toBeInTheDocument();

			await advance( 5 * 60 * 1000 );
			expect( callsTo( /sync-health$/ ) ).toHaveLength( 3 );
		} );

		it( 'shows Refresh as busy until they are in, and keeps it clickable', async () => {
			mockSequence( [ loading(), buildSummary() ] );
			render( <Harness /> );
			await screen.findByText( '70 of 100 products approved by Google' );

			const button = screen.getByRole( 'button', { name: 'Refresh' } );
			expect( button ).toHaveClass( 'is-busy' );
			expect( button ).toHaveAttribute( 'aria-busy', 'true' );
			expect( button ).toBeEnabled();

			await advance( 30 * 1000 );

			expect( button ).not.toHaveClass( 'is-busy' );
			expect( button ).toHaveAttribute( 'aria-busy', 'false' );
			expect( button ).toBeEnabled();
		} );

		it( 'never re-checks when nothing is being fetched', async () => {
			mockSequence( [ buildSummary() ] );
			render( <Harness /> );
			await screen.findByText( '70 of 100 products approved by Google' );
			const callsAfterLoad = apiFetch.mock.calls.length;

			await advance( 5 * 60 * 1000 );

			expect( apiFetch.mock.calls.length ).toBe( callsAfterLoad );
		} );

		it( 'drops a re-check that a manual Refresh overtakes', async () => {
			let finishCheck;
			let summaryCalls = 0;
			apiFetch.mockImplementation( ( { path } ) => {
				if ( path.includes( 'review-refresh' ) ) {
					return Promise.resolve( asResponse( { started: true } ) );
				}
				if ( path.includes( 'product-journey' ) ) {
					return Promise.resolve( asResponse( buildJourney() ) );
				}

				summaryCalls++;
				if ( summaryCalls === 2 ) {
					// The timed re-check answers last, with stale data.
					return new Promise( ( resolve ) => {
						finishCheck = () =>
							resolve(
								asResponse( loading( { state: 'failed' } ) )
							);
					} );
				}
				return Promise.resolve(
					asResponse(
						summaryCalls === 1 ? loading() : buildSummary()
					)
				);
			} );
			render( <Harness /> );
			await screen.findByText( '70 of 100 products approved by Google' );

			await advance( 30 * 1000 );
			expect( finishCheck ).toBeDefined();

			fireEvent.click(
				screen.getByRole( 'button', { name: 'Refresh' } )
			);
			await advance( 0 );
			expect( screen.getByText( 'Synced' ) ).toBeInTheDocument();

			await act( async () => finishCheck() );
			await advance( 5 * 60 * 1000 );

			expect( screen.getByText( 'Synced' ) ).toBeInTheDocument();
			expect(
				screen.queryByText( 'Sync failed' )
			).not.toBeInTheDocument();
			expect( summaryCalls ).toBe( 3 );
		} );
	} );
} );
