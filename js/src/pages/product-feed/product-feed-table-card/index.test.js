/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ProductFeedTableCard from './';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import useDataViewsScript from '~/hooks/useDataViewsScript';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '~/data';
import { recordGlaEvent, recordTablePageEvent } from '~/utils/tracks';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );
jest.mock( '~/hooks/useAppSelectDispatch', () => jest.fn() );
jest.mock( '~/hooks/useDataViewsScript', () => jest.fn() );
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
	recordTablePageEvent: jest.fn(),
} ) );

/**
 * Renders a cell like DataViews does: the field's `render`, or else its value,
 * shown as the matching element's label when the field has `elements`.
 *
 * @param {Object} field Field definition.
 * @param {Object} item Row item.
 * @return {*} Cell content.
 */
const renderCell = ( field, item ) => {
	if ( field.render ) {
		return field.render( { item } );
	}

	const value = field.getValue?.( { item } ) ?? item[ field.id ];
	return (
		field.elements?.find( ( element ) => element.value === value )?.label ??
		value
	);
};

/**
 * Whether an action applies to an item, as DataViews decides it.
 *
 * @param {Object} action Action definition.
 * @param {Object} item Row item.
 * @return {boolean} Whether the action is eligible.
 */
const isEligible = ( action, item ) =>
	! action.isEligible || action.isEligible( item );

/**
 * Selected rows that some bulk action applies to, as DataViews 4.22 hands them
 * to a bulk action.
 *
 * @param {Array} data Items.
 * @param {Array} actions Action definitions.
 * @param {Array} selection Selected item ids.
 * @param {Function} getItemId Returns an item's id.
 * @return {Array} Selected, selectable items.
 */
const selectedRows = ( data, actions, selection, getItemId ) =>
	data.filter(
		( item ) =>
			selection.includes( getItemId( item ) ) &&
			actions.some(
				( action ) => action.supportsBulk && isEligible( action, item )
			)
	);

/**
 * Stub for `window.wp.dataviews.DataViews` (4.22): renders rows of each field's
 * `render` output and the row's eligible actions, plus buttons that change the
 * view the way DataViews' own search, filter and pagination controls would.
 * Like DataViews, a row's checkbox is disabled when no bulk action applies to
 * it, and bulk actions with an icon run on the selected rows from a footer.
 *
 * @param {Object} props DataViews props.
 * @param {Array} props.data Items to render.
 * @param {Array} props.fields Field definitions.
 * @param {Array} props.actions Row actions.
 * @param {Object} props.view Current view.
 * @param {Function} props.onChangeView Called with a new view.
 * @param {Object} props.paginationInfo Total items and pages.
 * @param {Array} props.selection Selected item ids.
 * @param {Function} props.onChangeSelection Called with new selected ids.
 * @param {Function} props.getItemId Returns an item's id.
 */
const DataViewsStub = ( {
	data,
	fields,
	actions,
	view,
	onChangeView,
	paginationInfo,
	selection,
	onChangeSelection,
	getItemId,
} ) => (
	<>
		<button
			onClick={ () =>
				onChangeView( { ...view, search: 'hoodie', page: 1 } )
			}
		>
			Search hoodie
		</button>
		<button
			onClick={ () =>
				onChangeView( {
					...view,
					filters: [
						{
							field: 'visible',
							operator: 'is',
							value: 'dont-sync-and-show',
						},
					],
					page: 1,
				} )
			}
		>
			Filter hidden
		</button>
		<button
			onClick={ () =>
				onChangeView( {
					...view,
					sort: { field: 'title', direction: 'desc' },
				} )
			}
		>
			Sort by name descending
		</button>
		<button onClick={ () => onChangeView( { ...view, page: 2 } ) }>
			Next page
		</button>
		<span>{ `${ paginationInfo.totalPages } pages` }</span>
		<table>
			<thead>
				<tr>
					{ fields.map( ( field ) => (
						<th key={ field.id }>{ field.label }</th>
					) ) }
				</tr>
			</thead>
			<tbody>
				{ data.map( ( item ) => (
					<tr key={ item.id }>
						<td>
							<input
								type="checkbox"
								aria-label={ `Select ${ item.title }` }
								disabled={
									! actions.some(
										( action ) =>
											action.supportsBulk &&
											isEligible( action, item )
									)
								}
								checked={ selection.includes(
									getItemId( item )
								) }
								onChange={ ( event ) =>
									onChangeSelection(
										event.target.checked
											? [
													...selection,
													getItemId( item ),
											  ]
											: selection.filter(
													( id ) =>
														id !== getItemId( item )
											  )
									)
								}
							/>
						</td>
						{ fields.map( ( field ) => (
							<td key={ field.id }>
								{ renderCell( field, item ) }
							</td>
						) ) }
						<td>
							{ actions
								.filter( ( action ) =>
									isEligible( action, item )
								)
								.map( ( action ) => (
									<button
										key={ action.id }
										onClick={ () =>
											action.callback( [ item ] )
										}
									>
										{ action.label }
									</button>
								) ) }
						</td>
					</tr>
				) ) }
			</tbody>
		</table>
		<footer aria-label="Selection">
			{ actions
				// Like 4.22: icon bulk actions that apply to a selected row,
				// labelled with the action name, given every selected row.
				.filter(
					( action ) =>
						action.supportsBulk &&
						action.icon &&
						selectedRows(
							data,
							actions,
							selection,
							getItemId
						).some( ( item ) => isEligible( action, item ) )
				)
				.map( ( action ) => (
					<button
						key={ action.id }
						aria-label={ action.label }
						onClick={ () =>
							action.callback(
								selectedRows(
									data,
									actions,
									selection,
									getItemId
								)
							)
						}
					/>
				) ) }
		</footer>
	</>
);

const products = [
	{
		id: 11,
		title: 'Blue hoodie',
		visible: true,
		status: 'disapproved',
		sync_status_group: 'disapproved',
		issues: [
			{
				issue: 'Missing GTIN',
				code: 'missing_gtin',
				action: 'Add a GTIN to the product',
				action_url: 'https://support.google.com/merchants/answer/1',
				severity: 'error',
				source: 'merchant_center',
			},
			{
				issue: 'Invalid price',
				code: 'invalid_price',
				action: 'Set a price',
				action_url: '',
				severity: 'error',
				source: 'sync',
			},
		],
	},
	{
		id: 12,
		title: 'Red cap',
		visible: false,
		status: 'not_synced',
		sync_status_group: 'not_synced',
		issues: [],
	},
];

const counts = {
	total: 100,
	segments: {
		approved: 70,
		pending_sync: 3,
		pending_review: 5,
		disapproved: 12,
		not_synced: 10,
	},
};

const lastFeedQuery = () =>
	useAppSelectDispatch.mock.calls
		.filter( ( [ selector ] ) => selector === 'getMCProductFeed' )
		.at( -1 )[ 1 ];

/**
 * The bulk actions DataViews shows in its selection footer.
 *
 * @return {Object} Queries bound to the footer.
 */
const footer = () =>
	within( screen.getByRole( 'contentinfo', { hidden: true } ) );

describe( 'ProductFeedTableCard', () => {
	let updateMCProductVisibility;
	let createNotice;

	beforeEach( () => {
		window.wp = { dataviews: { DataViews: DataViewsStub } };
		updateMCProductVisibility = jest.fn( () => Promise.resolve() );
		createNotice = jest.fn();
		useAppDispatch.mockReturnValue( { updateMCProductVisibility } );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );
		global.wcSettings.adminUrl = 'https://example.com/wp-admin/';
		useDataViewsScript.mockReturnValue( 'ready' );
		useAppSelectDispatch.mockReturnValue( {
			hasFinishedResolution: true,
			data: { products, total: 25 },
		} );
		jest.spyOn( window, 'open' ).mockImplementation( () => null );
	} );

	afterEach( () => {
		jest.clearAllMocks();
		window.open.mockRestore();
		delete window.wp;
		delete global.wcSettings.adminUrl;
	} );

	it( 'requests the first page of all products, sorted by name', () => {
		render( <ProductFeedTableCard counts={ counts } /> );

		expect( lastFeedQuery() ).toEqual( {
			page: 1,
			per_page: 10,
			orderby: 'title',
			order: 'asc',
		} );
		expect( screen.getByText( '3 pages' ) ).toBeInTheDocument();
	} );

	it( 'shows a tab per status with its product count, and filters by the selected one', async () => {
		render( <ProductFeedTableCard counts={ counts } /> );

		const tabs = screen
			.getAllByRole( 'tab' )
			.map( ( tab ) => tab.textContent );
		expect( tabs ).toEqual( [
			'All (100)',
			'Approved (70)',
			'Pending sync (3)',
			'Pending review (5)',
			'Disapproved (12)',
			'Not synced (10)',
		] );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Next page' } )
		);
		await userEvent.click(
			screen.getByRole( 'tab', { name: 'Disapproved (12)' } )
		);

		expect( lastFeedQuery() ).toMatchObject( {
			journey: 'disapproved',
			page: 1,
		} );
	} );

	it( 'searches, filters by channel visibility, and sorts on the server', async () => {
		render( <ProductFeedTableCard counts={ counts } /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Search hoodie' } )
		);
		expect( lastFeedQuery() ).toMatchObject( { search: 'hoodie' } );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Filter hidden' } )
		);
		expect( lastFeedQuery() ).toMatchObject( {
			search: 'hoodie',
			visibility: 'dont-sync-and-show',
		} );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Sort by name descending' } )
		);
		expect( lastFeedQuery() ).toMatchObject( {
			orderby: 'title',
			order: 'desc',
		} );
	} );

	it( 'records page changes', async () => {
		render( <ProductFeedTableCard counts={ counts } /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Next page' } )
		);

		expect( lastFeedQuery() ).toMatchObject( { page: 2 } );
		expect( recordTablePageEvent ).toHaveBeenCalledWith(
			'product-feed',
			2,
			'next'
		);
	} );

	it( 'shows each product with its status and how many issues it has', () => {
		render( <ProductFeedTableCard counts={ counts } /> );

		const hoodie = within(
			screen.getByText( 'Blue hoodie' ).closest( 'tr' )
		);
		expect(
			hoodie.getByRole( 'cell', { name: 'Sync and show' } )
		).toBeInTheDocument();
		expect( hoodie.getByText( 'Disapproved' ) ).toBeInTheDocument();
		expect(
			hoodie.getByRole( 'button', {
				name: 'View 2 issues for Blue hoodie',
			} )
		).toBeInTheDocument();

		const cap = within( screen.getByText( 'Red cap' ).closest( 'tr' ) );
		expect(
			cap.getByRole( 'cell', { name: 'Don’t sync and show' } )
		).toBeInTheDocument();
		expect( cap.getByText( 'Not synced' ) ).toBeInTheDocument();
		expect( cap.getByText( '0' ) ).toBeInTheDocument();
		expect(
			cap.queryByRole( 'button', { name: /issue/ } )
		).not.toBeInTheDocument();
	} );

	describe( 'with a product hidden from the store catalog', () => {
		beforeEach( () => {
			useAppSelectDispatch.mockReturnValue( {
				hasFinishedResolution: true,
				data: {
					products: [
						{ ...products[ 0 ], catalog_visible: false },
						{ ...products[ 1 ], catalog_visible: true },
						{
							...products[ 1 ],
							id: 13,
							title: 'Green scarf',
							visible: true,
						},
					],
					total: 3,
				},
			} );
		} );

		it( 'shows it as Don’t sync and show, whatever its setting', () => {
			render( <ProductFeedTableCard counts={ counts } /> );

			const hoodie = within(
				screen.getByText( 'Blue hoodie' ).closest( 'tr' )
			);
			expect(
				hoodie.getByRole( 'cell', { name: 'Don’t sync and show' } )
			).toBeInTheDocument();
		} );

		it( 'offers each product only the visibility change it can take, and none for that one', () => {
			render( <ProductFeedTableCard counts={ counts } /> );
			const rowActions = ( title ) =>
				within( screen.getByText( title ).closest( 'tr' ) )
					.getAllByRole( 'button' )
					.map( ( button ) => button.textContent )
					.filter( ( text ) => ! text.includes( 'issue' ) );

			expect( rowActions( 'Blue hoodie' ) ).toEqual( [ 'Edit' ] );
			expect( rowActions( 'Red cap' ) ).toEqual( [
				'Edit',
				'Sync and show',
			] );
			expect( rowActions( 'Green scarf' ) ).toEqual( [
				'Edit',
				'Don’t sync and show',
			] );

			expect(
				screen.getByRole( 'checkbox', { name: 'Select Blue hoodie' } )
			).toBeDisabled();
			expect(
				screen.getByRole( 'checkbox', { name: 'Select Red cap' } )
			).toBeEnabled();
		} );

		it( 'changes only the selected products the action applies to, then confirms, clears the selection and reloads', async () => {
			const onVisibilityChange = jest.fn();
			render(
				<ProductFeedTableCard
					counts={ counts }
					onVisibilityChange={ onVisibilityChange }
				/>
			);

			// Red cap isn't synced and Green scarf is: only Red cap changes.
			await userEvent.click(
				screen.getByRole( 'checkbox', { name: 'Select Red cap' } )
			);
			await userEvent.click(
				screen.getByRole( 'checkbox', { name: 'Select Green scarf' } )
			);
			await userEvent.click(
				footer().getByRole( 'button', {
					name: 'Sync and show',
				} )
			);

			expect( updateMCProductVisibility ).toHaveBeenCalledWith(
				[ 12 ],
				true
			);
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_bulk_edit_click',
				{
					context: 'product-feed',
					number_of_items: 1,
					visibility_to: 'sync_and_show',
				}
			);
			expect( createNotice ).toHaveBeenCalledWith(
				'success',
				'You successfully changed the channel visibility of 1 product'
			);
			expect( onVisibilityChange ).toHaveBeenCalled();
			expect(
				screen.getByRole( 'checkbox', { name: 'Select Red cap' } )
			).not.toBeChecked();
			expect(
				screen.getByRole( 'checkbox', { name: 'Select Green scarf' } )
			).not.toBeChecked();
		} );

		it( 'shows icon bulk actions in the footer only for changes that apply to the selection', async () => {
			render( <ProductFeedTableCard counts={ counts } /> );
			const offered = () =>
				footer()
					.queryAllByRole( 'button' )
					.map( ( button ) => button.getAttribute( 'aria-label' ) );

			expect( offered() ).toEqual( [] );

			await userEvent.click(
				screen.getByRole( 'checkbox', { name: 'Select Red cap' } )
			);
			expect( offered() ).toEqual( [ 'Sync and show' ] );

			await userEvent.click(
				screen.getByRole( 'checkbox', { name: 'Select Green scarf' } )
			);
			expect( offered() ).toEqual( [
				'Sync and show',
				'Don’t sync and show',
			] );
		} );

		it( 'keeps the selection when the change fails', async () => {
			updateMCProductVisibility.mockRejectedValue( new Error( 'Nope' ) );
			const onVisibilityChange = jest.fn();
			render(
				<ProductFeedTableCard
					counts={ counts }
					onVisibilityChange={ onVisibilityChange }
				/>
			);

			await userEvent.click(
				screen.getByRole( 'checkbox', { name: 'Select Green scarf' } )
			);
			await userEvent.click(
				footer().getByRole( 'button', {
					name: 'Don’t sync and show',
				} )
			);

			expect( updateMCProductVisibility ).toHaveBeenCalledWith(
				[ 13 ],
				false
			);
			expect( createNotice ).not.toHaveBeenCalled();
			expect( onVisibilityChange ).not.toHaveBeenCalled();
			expect(
				screen.getByRole( 'checkbox', { name: 'Select Green scarf' } )
			).toBeChecked();
		} );

		it( 'reloads the counts when the change partly fails', async () => {
			updateMCProductVisibility.mockRejectedValue( {
				success: [ 13 ],
				errors: [ 12 ],
			} );
			const onVisibilityChange = jest.fn();
			render(
				<ProductFeedTableCard
					counts={ counts }
					onVisibilityChange={ onVisibilityChange }
				/>
			);

			await userEvent.click(
				screen.getByRole( 'checkbox', { name: 'Select Green scarf' } )
			);
			await userEvent.click(
				footer().getByRole( 'button', {
					name: 'Don’t sync and show',
				} )
			);

			expect( createNotice ).not.toHaveBeenCalled();
			expect( onVisibilityChange ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	it( 'lists a product’s issues in a modal', async () => {
		render( <ProductFeedTableCard counts={ counts } /> );

		await userEvent.click(
			screen.getByRole( 'button', {
				name: 'View 2 issues for Blue hoodie',
			} )
		);

		const dialog = within(
			screen.getByRole( 'dialog', { name: 'Blue hoodie' } )
		);
		expect( dialog.getByText( 'Missing GTIN' ) ).toBeInTheDocument();
		expect(
			dialog.getByText( 'Add a GTIN to the product' )
		).toBeInTheDocument();
		expect(
			dialog.getByRole( 'link', { name: /Learn more/ } )
		).toHaveAttribute(
			'href',
			'https://support.google.com/merchants/answer/1'
		);
		expect( dialog.getByText( 'Invalid price' ) ).toBeInTheDocument();
		expect( dialog.getByText( 'Sync error' ) ).toBeInTheDocument();
		// Severity is spelled out, not only shown as an icon.
		expect( dialog.getAllByText( 'Error' ) ).toHaveLength( 2 );
		expect( dialog.getAllByRole( 'link' ) ).toHaveLength( 1 );

		await userEvent.click(
			dialog.getByRole( 'link', { name: /Learn more/ } )
		);
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_documentation_link_click',
			{
				context: 'product-feed-issues-modal',
				link_id: 'missing_gtin',
				href: 'https://support.google.com/merchants/answer/1',
			}
		);
	} );

	it( 'edits a product in a new tab, recording the click', async () => {
		render( <ProductFeedTableCard counts={ counts } /> );

		const cap = within( screen.getByText( 'Red cap' ).closest( 'tr' ) );
		await userEvent.click( cap.getByRole( 'button', { name: 'Edit' } ) );

		expect( window.open ).toHaveBeenCalledWith(
			'https://example.com/wp-admin/post.php?post=12&action=edit',
			'_blank',
			'noopener,noreferrer'
		);
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_edit_product_click',
			{ status: 'not_synced', visibility: 'dont_sync_and_show' }
		);
	} );

	it( 'shows an error when the table cannot be loaded', () => {
		useDataViewsScript.mockReturnValue( 'failed' );
		const { container } = render(
			<ProductFeedTableCard counts={ counts } />
		);

		// Scoped to the card: the notice is also announced in a live region.
		expect(
			within( container ).getByText(
				'There was an error loading the product feed.'
			)
		).toBeInTheDocument();
		expect( screen.queryByRole( 'table' ) ).not.toBeInTheDocument();
	} );
} );
