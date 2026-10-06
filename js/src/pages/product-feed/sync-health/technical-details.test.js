/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { filterSortAndPaginate } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import TechnicalDetails from './technical-details';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

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
	state: 'healthy',
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

const Harness = () => {
	const [ open, setOpen ] = useState( false );
	return <TechnicalDetails open={ open } onOpenChange={ setOpen } />;
};

describe( 'TechnicalDetails', () => {
	beforeEach( () => {
		window.wp = {
			dataviews: { DataViews: DataViewsStub, filterSortAndPaginate },
		};
		apiFetch.mockImplementation( ( { path } ) =>
			Promise.resolve( path.includes( '/jobs' ) ? jobs : summary )
		);
	} );

	afterEach( () => {
		apiFetch.mockReset();
		delete window.wp;
	} );

	it( 'is collapsed and requests nothing until opened', async () => {
		render( <Harness /> );

		const toggle = screen.getByRole( 'button', {
			name: 'Advanced troubleshooting',
		} );
		expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
		expect( apiFetch ).not.toHaveBeenCalled();

		await userEvent.click( toggle );

		expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
		expect( await screen.findByText( '12345' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Does not match: syncing is off' )
		).toBeInTheDocument();
	} );

	it( 'shows each job with its state and links failed counts to Action Scheduler', async () => {
		render( <Harness /> );
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Advanced troubleshooting' } )
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
} );
