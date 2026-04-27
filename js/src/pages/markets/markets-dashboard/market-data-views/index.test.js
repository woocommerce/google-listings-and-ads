/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import MarketDataViews from './';
import useMarkets from '~/hooks/useMarkets';

jest.mock( '~/hooks/useMarkets' );

jest.mock( '../edit-market-modal', () =>
	jest.fn( ( { market, onRequestClose } ) => (
		<div data-testid="edit-market-modal">
			<span data-testid="edit-market-modal-name">{ market.label }</span>
			<button onClick={ onRequestClose }>Close modal</button>
		</div>
	) )
);

const SAMPLE_MARKETS = [
	{
		id: 'primary',
		label: 'Primary Market',
		countries: [ 'MU', 'ZW' ],
		language: 'en',
		currency: 'USD',
		feedLabel: 'ZW',
		shipping_rate: 'flat',
		shipping_time: 'flat',
		free_shipping: null,
	},
	{
		id: 'secondary',
		label: 'Secondary Market',
		countries: [ 'FR' ],
		language: 'fr',
		currency: 'EUR',
		feedLabel: 'FR',
		shipping_rate: 'table',
		shipping_time: 'table',
		free_shipping: null,
	},
];

/**
 * Stub for `window.wp.dataviews.DataViews` that captures the props it receives
 * and renders a minimal table so click flows can be exercised.
 */
const dataViewsCalls = [];
const DataViewsStub = ( props ) => {
	dataViewsCalls.push( props );

	const { fields, data, actions, isLoading } = props;

	if ( isLoading ) {
		return <div data-testid="dataviews-loading">Loading…</div>;
	}

	if ( ! data?.length ) {
		return <div data-testid="dataviews-empty">No items</div>;
	}

	return (
		<table>
			<thead>
				<tr>
					{ fields.map( ( field ) => (
						<th key={ field.id } scope="col">
							{ field.label }
						</th>
					) ) }
					{ actions?.length > 0 && <th scope="col">Actions</th> }
				</tr>
			</thead>
			<tbody>
				{ data.map( ( item ) => (
					<tr key={ item.id }>
						{ fields.map( ( field ) => (
							<td key={ field.id }>{ item[ field.id ] }</td>
						) ) }
						{ actions?.map( ( action ) => (
							<td key={ action.id }>
								<button
									onClick={ () =>
										action.callback( [ item ] )
									}
								>
									{ action.label }
								</button>
							</td>
						) ) }
					</tr>
				) ) }
			</tbody>
		</table>
	);
};

beforeEach( () => {
	dataViewsCalls.length = 0;
	window.wp = { dataviews: { DataViews: DataViewsStub } };
	useMarkets.mockReturnValue( {
		data: SAMPLE_MARKETS,
		hasFinishedResolution: true,
	} );
} );

afterEach( () => {
	useMarkets.mockReset();
	delete window.wp;
} );

describe( 'MarketDataViews', () => {
	test( 'renders two column headers: Market, Shipping times', () => {
		render( <MarketDataViews /> );

		expect(
			screen.getByRole( 'columnheader', { name: 'Market' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'columnheader', { name: 'Shipping times' } )
		).toBeInTheDocument();
	} );

	test( 'renders the Market cell as "<label> (<n> countries)"', () => {
		render( <MarketDataViews /> );

		expect(
			screen.getByRole( 'cell', { name: 'Primary Market (2 countries)' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'cell', { name: 'Secondary Market (1 country)' } )
		).toBeInTheDocument();
	} );

	test( 'renders the Shipping times cell from shipping_time', () => {
		render( <MarketDataViews /> );

		expect(
			screen.getByRole( 'cell', { name: 'flat' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'cell', { name: 'table' } )
		).toBeInTheDocument();
	} );

	test( 'configures every field with enableHiding=false and enableSorting=false', () => {
		render( <MarketDataViews /> );

		expect( dataViewsCalls ).toHaveLength( 1 );
		dataViewsCalls[ 0 ].fields.forEach( ( field ) => {
			expect( field.enableHiding ).toBe( false );
			expect( field.enableSorting ).toBe( false );
		} );
	} );

	test( 'exposes a single Edit action', () => {
		render( <MarketDataViews /> );

		expect( dataViewsCalls[ 0 ].actions ).toHaveLength( 1 );
		expect( dataViewsCalls[ 0 ].actions[ 0 ] ).toMatchObject( {
			id: 'edit',
			label: 'Edit',
		} );
	} );

	test( 'renders an Edit action button on each row', () => {
		render( <MarketDataViews /> );

		const editButtons = screen.getAllByRole( 'button', { name: 'Edit' } );
		expect( editButtons ).toHaveLength( SAMPLE_MARKETS.length );
	} );

	test( 'opens EditMarketModal with the clicked row when Edit is pressed', async () => {
		const user = userEvent.setup();
		render( <MarketDataViews /> );

		expect(
			screen.queryByTestId( 'edit-market-modal' )
		).not.toBeInTheDocument();

		const [ firstEditButton ] = screen.getAllByRole( 'button', {
			name: 'Edit',
		} );
		await user.click( firstEditButton );

		expect( screen.getByTestId( 'edit-market-modal' ) ).toBeInTheDocument();
		expect(
			screen.getByTestId( 'edit-market-modal-name' ).textContent
		).toBe( SAMPLE_MARKETS[ 0 ].label );
	} );

	test( 'closes EditMarketModal when onRequestClose is invoked', async () => {
		const user = userEvent.setup();
		render( <MarketDataViews /> );

		const [ firstEditButton ] = screen.getAllByRole( 'button', {
			name: 'Edit',
		} );
		await user.click( firstEditButton );
		expect( screen.getByTestId( 'edit-market-modal' ) ).toBeInTheDocument();

		await user.click(
			screen.getByRole( 'button', { name: 'Close modal' } )
		);
		expect(
			screen.queryByTestId( 'edit-market-modal' )
		).not.toBeInTheDocument();
	} );

	test( 'passes isLoading=!hasFinishedResolution to DataViews', () => {
		useMarkets.mockReturnValue( {
			data: [],
			hasFinishedResolution: false,
		} );

		render( <MarketDataViews /> );

		expect( dataViewsCalls[ 0 ].isLoading ).toBe( true );
	} );

	test( 'renders the DataViews empty state when markets is []', () => {
		useMarkets.mockReturnValue( {
			data: [],
			hasFinishedResolution: true,
		} );

		render( <MarketDataViews /> );

		expect( screen.getByTestId( 'dataviews-empty' ) ).toBeInTheDocument();
		expect(
			screen.queryByTestId( 'edit-market-modal' )
		).not.toBeInTheDocument();
	} );

	test( 'derives paginationInfo from markets.length', () => {
		render( <MarketDataViews /> );

		expect( dataViewsCalls[ 0 ].paginationInfo ).toEqual( {
			totalItems: SAMPLE_MARKETS.length,
			totalPages: 1,
		} );
	} );
} );
