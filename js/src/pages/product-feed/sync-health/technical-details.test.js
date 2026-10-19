/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';
import { filterSortAndPaginate } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import TechnicalDetails from './technical-details';

jest.mock( '@wordpress/api-fetch', () => {
	const mock = jest.fn();
	// The data store registers a middleware when it loads.
	mock.use = jest.fn();
	return mock;
} );

jest.mock( '~/hooks/useDataViewsScript', () => jest.fn( () => 'ready' ) );

/**
 * Stub for `window.wp.dataviews.DataViews`: renders each item as a table row of
 * its fields' `render` output (or `getValue`, as DataViews does), which is what
 * these tests assert on.
 *
 * @param {Object} props
 * @param {Array} props.data Items to render.
 * @param {Array} props.fields Field definitions.
 * @param {Function} props.getItemId Returns an item's key.
 */
const DataViewsStub = ( { data, fields, getItemId } ) => (
	<table>
		<tbody>
			{ data.map( ( item ) => (
				<tr key={ getItemId( item ) }>
					{ fields.map( ( field ) => (
						<td key={ field.id }>
							{ field.render
								? field.render( { item } )
								: field.getValue( { item } ) }
						</td>
					) ) }
				</tr>
			) ) }
		</tbody>
	</table>
);

const now = Math.floor( Date.now() / 1000 );

const summary = {
	generated_at: now,
	state: 'synced',
	reasons: [],
	pillars: {
		connection: {
			status: 'ok',
			google_connected: true,
			mc_setup_complete: true,
			jetpack_connected: true,
			merchant_id: 12345,
			circuit_breaker_open: false,
			circuit_breaker_retry_at: null,
			url_matches: 'no',
		},
		products: { syncable: 45, overdue_resubmit: 2 },
		shipping: { rate_mode: 'automatic', last_failure: null },
		review: {},
	},
	queue: {
		pending: 1,
		running: 0,
		failed_24h: 4,
		last_completed_at: now - 600,
	},
};

const jobs = {
	available: true,
	failure_threshold: 3,
	jobs: [
		{
			name: 'update_products',
			state: 'paused',
			pending: 0,
			running: 0,
			failed_24h: 4,
			last_completed_at: now - 7200,
			hooks: [],
		},
		{
			name: 'resubmit_expiring_products',
			state: 'scheduled',
			pending: 1,
			running: 0,
			failed_24h: 0,
			last_completed_at: now - 80000,
			hooks: [],
		},
	],
};

describe( 'TechnicalDetails', () => {
	beforeEach( () => {
		window.wp = {
			dataviews: { DataViews: DataViewsStub, filterSortAndPaginate },
		};
		// The hook calls apiFetch with `parse: false`, so answer with a Response stand-in.
		apiFetch.mockImplementation( () =>
			Promise.resolve( {
				json: () => Promise.resolve( jobs ),
				clone() {
					return this;
				},
			} )
		);
	} );

	afterEach( () => {
		apiFetch.mockReset();
		delete window.wp;
	} );

	it( 'is a modal showing the store details it was given', async () => {
		render(
			<TechnicalDetails
				summary={ summary }
				onRequestClose={ jest.fn() }
			/>
		);

		const dialog = screen.getByRole( 'dialog', {
			name: 'Advanced troubleshooting',
		} );
		expect( within( dialog ).getByText( '12345' ) ).toBeInTheDocument();
		expect(
			within( dialog ).getByText( 'Does not match: syncing is off' )
		).toBeInTheDocument();
		expect(
			await within( dialog ).findByText( 'update_products' )
		).toBeInTheDocument();
	} );

	it( 'fetches background jobs once when opened, and never again while open', async () => {
		jest.useFakeTimers();
		try {
			render(
				<TechnicalDetails
					summary={ summary }
					onRequestClose={ jest.fn() }
				/>
			);
			await screen.findByText( 'update_products' );

			await act( async () => {
				jest.advanceTimersByTime( 10 * 60 * 1000 );
			} );

			expect( apiFetch ).toHaveBeenCalledTimes( 1 );
			expect( apiFetch.mock.calls[ 0 ][ 0 ].path ).toMatch(
				/sync-health\/jobs$/
			);
		} finally {
			jest.useRealTimers();
		}
	} );

	it( 'shows each job with its state and links failed counts to Action Scheduler', async () => {
		render(
			<TechnicalDetails
				summary={ summary }
				onRequestClose={ jest.fn() }
			/>
		);

		const paused = ( await screen.findByText( 'update_products' ) ).closest(
			'tr'
		);
		expect( within( paused ).getByText( 'Paused' ) ).toBeInTheDocument();
		expect(
			within( paused ).getByRole( 'link', { name: /4/ } )
		).toHaveAttribute( 'href', expect.stringContaining( 'status=failed' ) );

		const scheduled = screen
			.getByText( 'resubmit_expiring_products' )
			.closest( 'tr' );
		expect(
			within( scheduled ).getByText( 'Scheduled' )
		).toBeInTheDocument();
	} );

	it( 'copies only the summary and the jobs as diagnostics', async () => {
		let copied;
		document.execCommand = jest.fn( () => {
			// The copy helper selects the text in a temporary textarea.
			copied = document.querySelector( 'textarea' ).value;
			return true;
		} );

		try {
			render(
				<TechnicalDetails
					summary={ summary }
					onRequestClose={ jest.fn() }
				/>
			);
			await screen.findByText( 'update_products' );

			await userEvent.click(
				screen.getByRole( 'button', { name: 'Copy diagnostics' } )
			);

			expect( JSON.parse( copied ) ).toEqual( { summary, jobs } );
			expect(
				screen.getByRole( 'button', { name: 'Copied' } )
			).toBeInTheDocument();
		} finally {
			delete document.execCommand;
		}
	} );

	it( 'says when no background jobs ran', async () => {
		apiFetch.mockImplementation( () =>
			Promise.resolve( {
				json: () => Promise.resolve( { ...jobs, jobs: [] } ),
				clone() {
					return this;
				},
			} )
		);
		render(
			<TechnicalDetails
				summary={ summary }
				onRequestClose={ jest.fn() }
			/>
		);

		expect(
			await screen.findByText(
				'No background jobs ran in the last 24 hours.'
			)
		).toBeInTheDocument();
	} );
} );
