/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import ProductOverview from './';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

jest.mock( '~/hooks/useAppSelectDispatch', () =>
	jest.fn( () => ( {
		hasFinishedResolution: true,
		data: { status: 'DISAPPROVED' },
	} ) )
);

const now = Math.floor( Date.now() / 1000 );

const pillar = ( status, extra = {} ) => ( { status, ...extra } );

const buildSummary = ( overrides = {} ) => ( {
	generated_at: now,
	state: 'healthy',
	reasons: [],
	pillars: {
		connection: pillar( 'ok' ),
		products: pillar( 'ok', {
			last_push_at: now - 300,
			full_sync: {
				in_progress: false,
				interrupted: false,
				last_completed_at: now - 86400,
			},
		} ),
		shipping: pillar( 'ok', {
			syncable: true,
			last_success_at: now - 7200,
			last_failure: null,
		} ),
		review: pillar( 'ok', { data_at: now - 600, loading: false } ),
	},
	queue: {},
	...overrides,
} );

const journey = {
	total: 100,
	segments: {
		live: 70,
		expiring: 2,
		in_review: 5,
		waiting: 3,
		retrying: 0,
		disapproved: 8,
		rejected: 4,
		hidden: 6,
		not_sent: 2,
	},
};

const mockApi = (
	summary,
	reviewRefresh = { started: true, reason: 'started', available_at: null }
) => {
	apiFetch.mockImplementation( ( { path } ) => {
		if ( path.includes( 'review-refresh' ) ) {
			return Promise.resolve( reviewRefresh );
		}
		return Promise.resolve(
			path.includes( 'product-journey' ) ? journey : summary
		);
	} );
};

const reviewRefreshCalls = () =>
	apiFetch.mock.calls.filter( ( [ { path } ] ) =>
		path.includes( 'review-refresh' )
	);

describe( 'ProductOverview', () => {
	afterEach( () => apiFetch.mockReset() );

	it( 'leads with how many products are live, counting expiring ones', async () => {
		mockApi( buildSummary() );
		render( <ProductOverview journey="" onJourneyChange={ jest.fn() } /> );

		expect(
			await screen.findByText( '72 of 100 products are live on Google' )
		).toBeInTheDocument();
		const header = within(
			screen.getByRole( 'heading', { name: 'Overview' } ).parentElement
		);
		expect( header.getByText( 'Synced' ) ).toBeInTheDocument();
		expect(
			header.getByText( 'All changes have been sent to Google' )
		).toBeInTheDocument();
	} );

	it( 'shows Approved, then the other states that have products, problems first', async () => {
		mockApi( buildSummary() );
		render( <ProductOverview journey="" onJourneyChange={ jest.fn() } /> );

		await screen.findByText( '72 of 100 products are live on Google' );
		const states = screen
			.getAllByRole( 'button' )
			.filter( ( button ) =>
				button.classList.contains( 'gla-product-overview__state' )
			)
			.map( ( button ) => button.textContent );

		expect( states ).toEqual( [
			expect.stringContaining( 'Approved70' ),
			expect.stringContaining( 'Data error4' ),
			expect.stringContaining( 'Disapproved8' ),
			expect.stringContaining( 'Expiring2' ),
			expect.stringContaining( 'Waiting to send3' ),
			expect.stringContaining( 'Pending review5' ),
			expect.stringContaining( 'Not synced2' ),
			expect.stringContaining( 'Don’t sync6' ),
		] );
		expect( screen.queryByText( 'Retrying' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'Select a state to filter the product table.' )
		).not.toBeInTheDocument();
	} );

	it( 'filters the table from a state, and clears it on a second click', async () => {
		mockApi( buildSummary() );
		const onJourneyChange = jest.fn();
		const { rerender } = render(
			<ProductOverview journey="" onJourneyChange={ onJourneyChange } />
		);

		await userEvent.click(
			await screen.findByRole( 'button', { name: /Not synced/ } )
		);
		expect( onJourneyChange ).toHaveBeenLastCalledWith( 'not_sent' );

		rerender(
			<ProductOverview
				journey="not_sent"
				onJourneyChange={ onJourneyChange }
			/>
		);
		const selected = screen.getByRole( 'button', { name: /Not synced/ } );
		expect( selected ).toHaveAttribute( 'aria-current', 'true' );
		// aria-pressed makes core Button render the dark `is-pressed` fill.
		expect( selected ).not.toHaveAttribute( 'aria-pressed' );
		expect( selected ).not.toHaveClass( 'is-pressed' );
		expect( selected ).toHaveClass( 'is-secondary' );

		await userEvent.click( selected );
		expect( onJourneyChange ).toHaveBeenLastCalledWith( '' );
	} );

	it( 'shows the top sync problem under the headline', async () => {
		mockApi(
			buildSummary( {
				state: 'paused',
				reasons: [
					{
						code: 'product_job_paused',
						pillar: 'products',
						severity: 'error',
						data: {},
					},
				],
			} )
		);
		render( <ProductOverview journey="" onJourneyChange={ jest.fn() } /> );

		expect(
			await screen.findByText(
				'Product sync stopped after failing several times in a row.'
			)
		).toBeInTheDocument();
		expect( screen.getByText( 'Sync paused' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Needs your attention' )
		).not.toBeInTheDocument();
	} );

	it( 'shows connection, account, and when each sync last ran as one list', async () => {
		mockApi( buildSummary() );
		render( <ProductOverview journey="" onJourneyChange={ jest.fn() } /> );

		const row = async ( label ) =>
			( await screen.findByText( label ) ).closest(
				'.gla-product-overview__status-row, .gla-detail-grid__item'
			);

		expect(
			within( await row( 'Google connection' ) ).getByText( 'Healthy' )
		).toBeInTheDocument();

		const account = await row( 'Merchant Center account' );
		expect(
			within( account ).getByText( 'Disapproved' )
		).toBeInTheDocument();
		expect(
			within( account ).getByText(
				'To make products eligible to show on Google, fix all setup and policy issues that were found.'
			)
		).toBeInTheDocument();

		[
			'Last product update',
			'Last full sync',
			'Last review results',
			'Last shipping sync',
		].forEach( ( label ) =>
			expect( screen.getByText( label ) ).toBeInTheDocument()
		);
		expect(
			within( await row( 'Last shipping sync' ) ).getByText( /ago/ )
		).toBeInTheDocument();
	} );

	it( 'says it is syncing, and how many product changes are being sent', async () => {
		mockApi( buildSummary( { state: 'syncing' } ) );
		render( <ProductOverview journey="" onJourneyChange={ jest.fn() } /> );

		const header = within(
			( await screen.findByRole( 'heading', { name: 'Overview' } ) )
				.parentElement
		);
		expect( header.getByText( 'Syncing' ) ).toBeInTheDocument();
		expect(
			await header.findByText( 'Sending 3 product changes to Google' )
		).toBeInTheDocument();
	} );

	it( 'opens technical details from the status list', async () => {
		mockApi( buildSummary() );
		const onShowTechnicalDetails = jest.fn();
		render(
			<ProductOverview
				journey=""
				onJourneyChange={ jest.fn() }
				onShowTechnicalDetails={ onShowTechnicalDetails }
			/>
		);

		await userEvent.click(
			await screen.findByRole( 'button', {
				name: 'View advanced troubleshooting',
			} )
		);
		expect( onShowTechnicalDetails ).toHaveBeenCalled();
	} );

	it( 'always shows Approved first, disabled when there are none', async () => {
		apiFetch.mockImplementation( ( { path } ) =>
			Promise.resolve(
				path.includes( 'product-journey' )
					? {
							total: 16,
							segments: {
								...journey.segments,
								live: 0,
								expiring: 0,
							},
					  }
					: buildSummary()
			)
		);
		render( <ProductOverview journey="" onJourneyChange={ jest.fn() } /> );

		const approved = await screen.findByRole( 'button', {
			name: /^Approved\s*0/,
		} );
		expect( approved ).toBeDisabled();
	} );

	it( 'never fetches review results from Google while polling', async () => {
		jest.useFakeTimers();
		try {
			mockApi( buildSummary( { state: 'syncing' } ) );
			render(
				<ProductOverview journey="" onJourneyChange={ jest.fn() } />
			);
			await screen.findByText( '72 of 100 products are live on Google' );

			// Several polling cycles of the summary and the journey counts.
			for ( let i = 0; i < 5; i++ ) {
				await act( async () => {
					jest.advanceTimersByTime( 30 * 1000 );
				} );
			}

			const summaryCalls = apiFetch.mock.calls.filter( ( [ { path } ] ) =>
				/sync-health(\?|$)/.test( path )
			);
			expect( summaryCalls.length ).toBeGreaterThan( 1 );
			expect( reviewRefreshCalls() ).toHaveLength( 0 );
		} finally {
			jest.useRealTimers();
		}
	} );

	it( 'fetches review results once when Refresh is clicked, then re-checks status', async () => {
		mockApi( buildSummary() );
		render( <ProductOverview journey="" onJourneyChange={ jest.fn() } /> );
		await screen.findByText( '72 of 100 products are live on Google' );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Refresh' } )
		);

		expect( reviewRefreshCalls() ).toHaveLength( 1 );
		expect( reviewRefreshCalls()[ 0 ][ 0 ].method ).toBe( 'POST' );
		await waitFor( () =>
			expect(
				apiFetch.mock.calls.some( ( [ { path } ] ) =>
					path.includes( 'sync-health?refresh=true' )
				)
			).toBe( true )
		);
		expect(
			screen.getByRole( 'button', { name: 'Refresh' } )
		).toBeEnabled();
	} );

	it( 'explains when review results were fetched too recently', async () => {
		mockApi( buildSummary(), {
			started: false,
			reason: 'recent',
			available_at: now + 180,
		} );
		render( <ProductOverview journey="" onJourneyChange={ jest.fn() } /> );
		await screen.findByText( '72 of 100 products are live on Google' );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Refresh' } )
		);

		expect(
			await screen.findByText(
				/Review results were fetched recently\. New results can be fetched in 3 minutes\./
			)
		).toBeInTheDocument();
	} );
} );
