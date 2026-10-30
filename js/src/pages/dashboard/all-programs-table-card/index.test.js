/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { act, render, screen, within } from '@testing-library/react';
import { filterSortAndPaginate } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import AllProgramsTableCard from './';
import { CAMPAIGN_ASSETS_TOUR_SELECTOR } from './programs-data-views';
import { PMAX_TITLE_CLASS_NAME } from './useProgramsDataViewsConfig';
import CampaignAssetsTour from '~/components/tours/campaign-assets-tour';
import useDataViewsScript from '~/hooks/useDataViewsScript';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import useRaiseBudgetRecommendations from '~/hooks/useRaiseBudgetRecommendations';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/hooks/useDataViewsScript', () => jest.fn() );

jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn(),
} ) );

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockReturnValue( {} ),
} ) );

jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockReturnValue( {} )
);

jest.mock( '~/hooks/useEuPoliticalDeclarationContext', () =>
	jest.fn().mockReturnValue( { handleError: jest.fn() } )
);

jest.mock( '~/components/tours/campaign-assets-tour', () =>
	jest
		.fn( () => <div role="dialog" aria-label="tour" /> )
		.mockName( 'CampaignAssetsTour' )
);

jest.mock( '~/hooks/useCountryKeyNameMap', () =>
	jest.fn().mockReturnValue( { US: 'United States (US)' } )
);

jest.mock( '~/hooks/useAdsCurrency', () =>
	jest.fn().mockReturnValue( { formatAmount: ( value ) => `$${ value }` } )
);

jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest.fn().mockReturnValue( { data: [ 'US' ] } )
);

jest.mock( '~/hooks/useAdsCampaigns', () => jest.fn() );

jest.mock( '~/hooks/useRaiseBudgetRecommendations', () => jest.fn() );

/**
 * Stub for `window.wp.dataviews.DataViews` that records the props it receives.
 */
const dataViewsCalls = [];
const DataViewsStub = ( props ) => {
	dataViewsCalls.push( props );
	return <div data-testid="dataviews" />;
};
const lastProps = () => dataViewsCalls.at( -1 );
const changeView = ( change ) =>
	act( () => lastProps().onChangeView( { ...lastProps().view, ...change } ) );
const renderedIds = () => lastProps().data.map( ( { id } ) => id );

/**
 * Builds campaigns named "Campaign 01", "Campaign 02", … with daily budgets equal to their IDs.
 *
 * @param {number} count Number of campaigns.
 * @param {Object} [overrides] Properties to apply to every campaign.
 * @return {Array<Object>} Campaigns.
 */
const buildCampaigns = ( count, overrides = {} ) =>
	Array.from( { length: count }, ( _, index ) => ( {
		id: index + 1,
		name: `Campaign ${ String( index + 1 ).padStart( 2, '0' ) }`,
		status: 'enabled',
		type: 'shopping',
		amount: index + 1,
		displayCountries: [ 'US' ],
		...overrides,
	} ) );

const renderCard = () =>
	render( <AllProgramsTableCard trackEventReportId="dashboard" /> );

describe( 'AllProgramsTableCard', () => {
	beforeEach( () => {
		dataViewsCalls.length = 0;
		window.wp = {
			dataviews: { DataViews: DataViewsStub, filterSortAndPaginate },
		};
		useDataViewsScript.mockReturnValue( 'ready' );
		useAdsCampaigns.mockReturnValue( { data: buildCampaigns( 2 ) } );
		useRaiseBudgetRecommendations.mockReturnValue( { campaigns: [] } );
		recordGlaEvent.mockClear();
		CampaignAssetsTour.mockClear();
	} );

	afterEach( () => {
		delete window.wp;
	} );

	it( 'renders the title and the add campaign button in the card', () => {
		const { container } = renderCard();
		const card = container.querySelector( '.gla-all-programs-table-card' );

		expect(
			within( card ).getByRole( 'heading', { name: 'Programs' } )
		).toBeInTheDocument();
		expect(
			within( card ).getByRole( 'button', { name: 'Add campaign' } )
		).toBeInTheDocument();
	} );

	it( 'renders a spinner while the DataViews script is loading', () => {
		useDataViewsScript.mockReturnValue( 'loading' );
		renderCard();

		expect(
			screen.getByRole( 'status', { name: 'Loading…' } )
		).toBeInTheDocument();
		expect( screen.queryByTestId( 'dataviews' ) ).not.toBeInTheDocument();
	} );

	it( 'renders a warning instead of the table when the DataViews script fails to load', () => {
		useDataViewsScript.mockReturnValue( 'failed' );
		const { container } = renderCard();
		const card = container.querySelector( '.gla-all-programs-table-card' );

		expect(
			within( card ).getByText(
				'There was an error loading the programs.'
			)
		).toBeInTheDocument();
		expect( screen.queryByTestId( 'dataviews' ) ).not.toBeInTheDocument();
	} );

	it( 'passes 25 rows per page and a default sort to DataViews', () => {
		useAdsCampaigns.mockReturnValue( { data: buildCampaigns( 30 ) } );
		renderCard();

		expect( lastProps().data ).toHaveLength( 25 );
		expect( lastProps().paginationInfo ).toEqual( {
			totalItems: 31,
			totalPages: 2,
		} );
		expect( lastProps().view ).toMatchObject( {
			perPage: 25,
			sort: { field: 'title', direction: 'asc' },
			titleField: 'title',
		} );
		expect( lastProps().search ).toBe( false );
	} );

	it( 'changes the rows per page', () => {
		useAdsCampaigns.mockReturnValue( { data: buildCampaigns( 30 ) } );
		renderCard();

		changeView( { perPage: 50 } );

		expect( lastProps().data ).toHaveLength( 31 );
		expect( lastProps().paginationInfo.totalPages ).toBe( 1 );
	} );

	it( 'sorts all programs before paging', () => {
		useAdsCampaigns.mockReturnValue( { data: buildCampaigns( 30 ) } );
		renderCard();

		changeView( { sort: { field: 'budget', direction: 'desc' } } );
		changeView( { page: 2 } );

		// Budgets equal IDs; the product feed (ID 0) has the lowest budget.
		expect( renderedIds() ).toEqual( [ 5, 4, 3, 2, 1, 0 ] );
	} );

	it( 'moves back to the last page when it no longer has rows', () => {
		useAdsCampaigns.mockReturnValue( { data: buildCampaigns( 25 ) } );
		const { rerender } = renderCard();

		changeView( { page: 2 } );
		expect( renderedIds() ).toHaveLength( 1 );

		useAdsCampaigns.mockReturnValue( { data: buildCampaigns( 24 ) } );
		rerender( <AllProgramsTableCard trackEventReportId="dashboard" /> );

		expect( lastProps().view.page ).toBe( 1 );
		expect( renderedIds() ).toHaveLength( 25 );
	} );

	describe( 'tracking', () => {
		// The default sort is title ascending, so sorting by title uses the descending direction.
		it.each( [
			[ 'title', 'desc', 'id' ],
			[ 'country', 'asc', 'country' ],
			[ 'budget', 'desc', 'dailyBudget' ],
			[ 'status', 'asc', 'enabled' ],
		] )(
			'records sorting by %s with the legacy column name',
			( field, direction, column ) => {
				renderCard();

				changeView( { sort: { field, direction } } );

				expect( recordGlaEvent ).toHaveBeenCalledWith(
					'gla_table_sort',
					{ report: 'dashboard', column, direction }
				);
			}
		);

		it( 'records toggling a column with the legacy column name', () => {
			renderCard();

			changeView( { fields: [ 'country', 'budget' ] } );
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_table_header_toggle',
				{ report: 'dashboard', column: 'enabled', status: 'off' }
			);

			changeView( { fields: [ 'country', 'budget', 'status' ] } );
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_table_header_toggle',
				{ report: 'dashboard', column: 'enabled', status: 'on' }
			);
		} );

		it( 'records nothing when only the page changes', () => {
			useAdsCampaigns.mockReturnValue( { data: buildCampaigns( 30 ) } );
			renderCard();

			changeView( { page: 2 } );

			expect( recordGlaEvent ).not.toHaveBeenCalledWith(
				'gla_table_sort',
				expect.anything()
			);
			expect( recordGlaEvent ).not.toHaveBeenCalledWith(
				'gla_table_header_toggle',
				expect.anything()
			);
		} );

		it( 'records the campaigns that have budget recommendations', () => {
			useRaiseBudgetRecommendations.mockReturnValue( {
				campaigns: [ { campaign_id: 2 }, { campaign_id: 99 } ],
			} );
			renderCard();

			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_raise_budget_recommendation_badge_campaigns',
				{ context: 'programs-table-card', campaign_ids: [ 2 ] }
			);
		} );
	} );

	describe( 'campaign assets tour', () => {
		it( 'is not rendered when there is no PMax campaign', () => {
			renderCard();

			expect( CampaignAssetsTour ).not.toHaveBeenCalled();
		} );

		it( 'points at the actions cell of a PMax row', () => {
			useAdsCampaigns.mockReturnValue( {
				data: buildCampaigns( 2, { type: 'performance_max' } ),
			} );
			renderCard();

			const { referenceElementCssSelector } =
				CampaignAssetsTour.mock.lastCall[ 0 ];
			expect( referenceElementCssSelector ).toBe(
				CAMPAIGN_ASSETS_TOUR_SELECTOR
			);
		} );

		it( 'selects the actions cell of the first PMax row in the table markup', () => {
			document.body.innerHTML = `
				<div class="gla-all-programs-table-card">
					<table>
						<tr id="shopping">
							<td><div>Shopping</div></td>
							<td class="dataviews-view-table__actions-column"></td>
						</tr>
						<tr id="pmax">
							<td><div class="${ PMAX_TITLE_CLASS_NAME }">PMax</div></td>
							<td class="dataviews-view-table__actions-column"></td>
						</tr>
					</table>
				</div>
			`;

			const cell = document.querySelector(
				CAMPAIGN_ASSETS_TOUR_SELECTOR
			);

			expect( cell.closest( 'tr' ).id ).toBe( 'pmax' );
			document.body.innerHTML = '';
		} );

		it( 'is not rendered when the first page has no PMax campaign', () => {
			const campaigns = buildCampaigns( 30 );
			campaigns[ 29 ].type = 'performance_max';
			useAdsCampaigns.mockReturnValue( { data: campaigns } );
			renderCard();

			changeView( { page: 2 } );

			expect( CampaignAssetsTour ).not.toHaveBeenCalled();
		} );

		it( 'is not mounted again after the view changes', () => {
			useAdsCampaigns.mockReturnValue( {
				data: buildCampaigns( 30, { type: 'performance_max' } ),
			} );
			const { queryByRole } = renderCard();

			expect(
				queryByRole( 'dialog', { name: 'tour' } )
			).toBeInTheDocument();

			changeView( { page: 2 } );
			expect(
				queryByRole( 'dialog', { name: 'tour' } )
			).not.toBeInTheDocument();

			changeView( { page: 1 } );
			expect(
				queryByRole( 'dialog', { name: 'tour' } )
			).not.toBeInTheDocument();
		} );
	} );

	it( 'passes the program actions to DataViews', () => {
		renderCard();

		expect( lastProps().actions.map( ( { id } ) => id ) ).toEqual( [
			'edit',
			'pause',
			'resume',
			'remove',
		] );
	} );
} );
