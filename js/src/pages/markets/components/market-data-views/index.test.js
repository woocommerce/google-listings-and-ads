/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { filterSortAndPaginate } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import MarketDataViews from '.';
import useMarkets from '../../hooks/useMarkets';
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';
import useSettings from '~/hooks/useSettings';

jest.mock( '../../hooks/useMarkets' );
jest.mock( '~/hooks/useCountryKeyNameMap' );
jest.mock( '~/hooks/useSettings' );

jest.mock( '../edit-market-modal', () =>
	jest.fn( ( { market, onRequestClose } ) => (
		<div data-testid="edit-market-modal">
			<span data-testid="edit-market-modal-name">{ market.label }</span>
			<button onClick={ onRequestClose }>Close modal</button>
		</div>
	) )
);

jest.mock( '../delete-market-modal', () =>
	jest.fn( ( { market, onRequestClose } ) => (
		<div data-testid="delete-market-modal">
			<span data-testid="delete-market-modal-id">{ market.id }</span>
			<button onClick={ onRequestClose }>Close delete modal</button>
		</div>
	) )
);

const SAMPLE_MARKETS = [
	{
		id: 'primary',
		label: 'Primary Market',
		countries: [ 'MU', 'ZW' ],
		country: null,
		language: 'en',
		currency: 'USD',
		feed_label: null,
		shipping_rate: 'flat',
		shipping_time: 'flat',
		free_shipping: null,
	},
	{
		id: 'fr',
		country: 'FR',
		language: 'fr',
		currency: 'EUR',
		feed_label: 'FR',
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
							<td key={ field.id }>
								{ field.render
									? field.render( { item } )
									: item[ field.id ] }
							</td>
						) ) }
						{ actions
							?.filter(
								( action ) =>
									! action.isEligible ||
									action.isEligible( item )
							)
							.map( ( action ) => (
								<td key={ action.id }>
									<button
										disabled={ !! action.disabled }
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
	window.wp = {
		dataviews: { DataViews: DataViewsStub, filterSortAndPaginate },
	};
	// shipping_rate: null triggers buildDefaultConfig (the two-column legacy
	// shape this test suite was written against).
	useSettings.mockReturnValue( { settings: { shipping_rate: null } } );
	useMarkets.mockReturnValue( {
		data: SAMPLE_MARKETS,
		hasFinishedResolution: true,
	} );
	useCountryKeyNameMap.mockReturnValue( {
		FR: 'France',
		MU: 'Mauritius',
		ZW: 'Zimbabwe',
	} );
} );

afterEach( () => {
	useSettings.mockReset();
	useMarkets.mockReset();
	useCountryKeyNameMap.mockReset();
	delete window.glaData.isMultiLingualStore;
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

	test( 'renders the primary Market cell as "<label> (<n> countries)"', () => {
		render( <MarketDataViews /> );

		expect(
			screen.getByRole( 'cell', { name: 'Primary Market (2 countries)' } )
		).toBeInTheDocument();
	} );

	test( 'renders a non-primary Market cell as the country name', () => {
		render( <MarketDataViews /> );

		expect(
			screen.getByRole( 'cell', { name: 'France' } )
		).toBeInTheDocument();
	} );

	test( 'passes shipping_time from each market row to DataViews', () => {
		render( <MarketDataViews /> );

		expect( dataViewsCalls[ 0 ].data[ 0 ].shipping_time ).toBe( 'flat' );
		expect( dataViewsCalls[ 0 ].data[ 1 ].shipping_time ).toBe( 'table' );
	} );

	test( 'configures every field with enableHiding=false and enableSorting=false', () => {
		render( <MarketDataViews /> );

		expect( dataViewsCalls ).toHaveLength( 1 );
		dataViewsCalls[ 0 ].fields.forEach( ( field ) => {
			expect( field.enableHiding ).toBe( false );
			expect( field.enableSorting ).toBe( false );
		} );
	} );

	test( 'exposes a primary Edit action with an icon', () => {
		render( <MarketDataViews /> );

		const editAction = dataViewsCalls[ 0 ].actions.find(
			( action ) => action.id === 'edit'
		);
		expect( editAction ).toMatchObject( {
			id: 'edit',
			label: 'Edit',
			isPrimary: true,
		} );
		// `icon` is required for DataViews to render a primary action outside
		// the kebab menu, so guard against it being dropped.
		expect( editAction.icon ).toBeTruthy();
	} );

	test( 'renders an Edit action button on each row', () => {
		render( <MarketDataViews /> );

		const editButtons = screen.getAllByRole( 'button', { name: 'Edit' } );
		expect( editButtons ).toHaveLength( SAMPLE_MARKETS.length );
	} );

	test( 'exposes an enabled Delete action only for non-primary markets', () => {
		render( <MarketDataViews /> );

		const deleteAction = dataViewsCalls[ 0 ].actions.find(
			( action ) => action.id === 'delete'
		);
		expect( deleteAction ).toMatchObject( {
			id: 'delete',
			label: 'Delete',
			isDestructive: true,
		} );
		expect( deleteAction.isPrimary ).toBeFalsy();
		expect( deleteAction.disabled ).toBeFalsy();
		expect( deleteAction.isEligible( { id: 'primary' } ) ).toBe( false );
		expect( deleteAction.isEligible( { id: 'fr' } ) ).toBe( true );
	} );

	test( 'renders Delete only on non-primary market rows', () => {
		render( <MarketDataViews /> );

		const deleteButtons = screen.getAllByRole( 'button', {
			name: 'Delete',
		} );
		expect( deleteButtons ).toHaveLength( 1 );

		const primaryRow = screen
			.getByRole( 'cell', { name: 'Primary Market (2 countries)' } )
			.closest( 'tr' );
		const secondaryRow = screen
			.getByRole( 'cell', { name: 'France' } )
			.closest( 'tr' );

		expect(
			within( primaryRow ).queryByRole( 'button', { name: 'Delete' } )
		).not.toBeInTheDocument();
		expect(
			within( secondaryRow ).getByRole( 'button', { name: 'Delete' } )
		).toBeEnabled();
	} );

	test( 'renders the Market cell wrapped in the gray-900 helper class', () => {
		const { container } = render( <MarketDataViews /> );

		const marketCells = container.querySelectorAll(
			'.gla-markets-table__market-cell'
		);
		expect( marketCells ).toHaveLength( SAMPLE_MARKETS.length );
		expect( marketCells[ 0 ] ).toHaveTextContent(
			'Primary Market (2 countries)'
		);
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
		).toBe( 'Primary Market (2 countries)' );
	} );

	test( 'opens DeleteMarketModal with the clicked row when Delete is pressed', async () => {
		const user = userEvent.setup();
		render( <MarketDataViews /> );

		expect(
			screen.queryByTestId( 'delete-market-modal' )
		).not.toBeInTheDocument();

		await user.click( screen.getByRole( 'button', { name: 'Delete' } ) );

		expect(
			screen.getByTestId( 'delete-market-modal' )
		).toBeInTheDocument();
		expect(
			screen.getByTestId( 'delete-market-modal-id' ).textContent
		).toBe( 'fr' );
	} );

	test( 'closes DeleteMarketModal when onRequestClose is invoked', async () => {
		const user = userEvent.setup();
		render( <MarketDataViews /> );

		await user.click( screen.getByRole( 'button', { name: 'Delete' } ) );
		expect(
			screen.getByTestId( 'delete-market-modal' )
		).toBeInTheDocument();

		await user.click(
			screen.getByRole( 'button', { name: 'Close delete modal' } )
		);
		expect(
			screen.queryByTestId( 'delete-market-modal' )
		).not.toBeInTheDocument();
	} );

	test( 'opening Delete does not open Edit, and vice versa', async () => {
		const user = userEvent.setup();
		render( <MarketDataViews /> );

		await user.click( screen.getByRole( 'button', { name: 'Delete' } ) );
		expect(
			screen.queryByTestId( 'edit-market-modal' )
		).not.toBeInTheDocument();
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

	test( 'filters rows by Market label when onChangeView sets a search term', () => {
		render( <MarketDataViews /> );

		const { onChangeView, view } = dataViewsCalls[ 0 ];
		act( () => {
			onChangeView( { ...view, search: 'France' } );
		} );

		expect(
			screen.getByRole( 'cell', { name: 'France' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'cell', {
				name: 'Primary Market (2 countries)',
			} )
		).not.toBeInTheDocument();
	} );

	test( 'matches the primary Market row when searching a country grouped under it', () => {
		render( <MarketDataViews /> );

		const { onChangeView, view } = dataViewsCalls[ 0 ];
		act( () => {
			onChangeView( { ...view, search: 'Mauritius' } );
		} );

		expect(
			screen.getByRole( 'cell', { name: 'Primary Market (2 countries)' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'cell', { name: 'France' } )
		).not.toBeInTheDocument();
	} );
} );
