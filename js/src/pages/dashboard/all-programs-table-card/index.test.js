/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, within, render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AllProgramsTableCard from './';
import CampaignAssetsTour from '~/components/tours/campaign-assets-tour';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import useRaiseBudgetRecommendations from '~/hooks/useRaiseBudgetRecommendations';

jest.mock( '~/hooks/useEuPoliticalDeclarationContext', () =>
	jest.fn().mockReturnValue( {
		showModal: jest.fn(),
		hideModal: jest.fn(),
		handleError: jest.fn(),
	} )
);

jest.mock( '~/components/tours/campaign-assets-tour', () =>
	jest
		.fn()
		.mockReturnValue( <div aria-label="tour" role="dialog" /> )
		.mockName( 'CampaignAssetsTour' )
);

jest.mock( '~/hooks/useCountryKeyNameMap', () =>
	jest
		.fn()
		.mockReturnValue( { US: 'United States (US)', JP: 'Japan' } )
		.mockName( 'useCountryKeyNameMap' )
);

jest.mock( '~/hooks/useAdsCurrency', () =>
	jest.fn().mockReturnValue( {
		formatAmount: jest.fn( ( value ) => value ),
	} )
);

jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest
		.fn()
		.mockReturnValue( { data: [ 'US', 'JP' ] } )
		.mockName( 'useTargetAudienceFinalCountryCodes' )
);

jest.mock( '~/hooks/useAdsCampaigns', () =>
	jest.fn().mockName( 'useAdsCampaigns' )
);

jest.mock( '~/hooks/useRaiseBudgetRecommendations', () =>
	jest.fn().mockName( 'useRaiseBudgetRecommendations' )
);

const mockedRecommendations = [
	{
		id: 10,
		type: 'CAMPAIGN_BUDGET',
		resource_name:
			'customers/{customer_id}/recommendations/{recommendation_id}',
		campaign_id: 10,
		campaign_name: 'Spring Campaign',
		campaign_status: 'ENABLED',
		last_synced: '2024-06-01T12:34:56Z',
	},
	{
		id: 11,
		type: 'MARGINAL_ROI_CAMPAIGN_BUDGET',
		resource_name:
			'customers/{customer_id}/recommendations/{recommendation_id}',
		campaign_id: 11,
		campaign_name: 'Summer Campaign',
		campaign_status: 'ENABLED',
		last_synced: '2024-06-02T12:34:56Z',
	},
	{
		id: 122,
		type: 'MARGINAL_ROI_CAMPAIGN_BUDGET',
		resource_name:
			'customers/{customer_id}/recommendations/{recommendation_id}',
		campaign_id: 122,
		campaign_name: 'Winter Campaign',
		campaign_status: 'ENABLED',
		last_synced: '2024-06-03T12:34:56Z',
	},
];

describe( 'AllProgramsTableCard', () => {
	const pmaxCampaign = {
		id: 10,
		name: 'PMax Campaign',
		status: 'enabled',
		type: 'performance_max',
		amount: 20,
		displayCountries: [ 'US' ],
	};

	const pmaxCampaignDisabled = {
		id: 11,
		name: 'Disabled PMax Campaign',
		status: 'disabled',
		type: 'performance_max',
		amount: 30,
		displayCountries: [ 'US', 'JP' ],
	};

	const shoppingCampaign = {
		id: 12,
		name: 'Shopping Campaign',
		status: 'enabled',
		type: 'shopping',
		amount: 50,
		displayCountries: [ 'JP' ],
	};

	const getEditButton = ( container ) =>
		within( container ).queryByRole( 'button', { name: /edit/i } );

	const getRemoveButton = ( container ) =>
		within( container ).queryByRole( 'button', { name: /remove/i } );

	const clickHeader = ( label, times = 1 ) => {
		const header = screen.getByRole( 'columnheader', {
			name: new RegExp( label, 'i' ),
		} );
		const button = within( header ).getByRole( 'button' );
		for ( let i = 0; i < times; i++ ) {
			fireEvent.click( button );
		}
	};

	let mockCampaigns;

	beforeAll( () => {
		useRaiseBudgetRecommendations.mockReturnValue( {
			campaigns: mockedRecommendations,
		} );
	} );

	beforeEach( () => {
		let mockedCampaigns = [];

		useAdsCampaigns.mockImplementation( () => {
			return { data: mockedCampaigns };
		} );

		mockCampaigns = ( ...campaigns ) => {
			mockedCampaigns = campaigns;
		};
	} );

	it( 'Should render the product feed row with a checked toggle in the disabled state', () => {
		render( <AllProgramsTableCard /> );

		const row = screen.getByRole( 'row', { name: /product feed/i } );
		const checkbox = within( row ).getByRole( 'checkbox' );

		expect( row ).toBeInTheDocument();
		expect( checkbox ).toBeChecked();
		expect( checkbox ).toBeDisabled();
	} );

	it( 'Should render the product feed row without the edit and remove buttons', () => {
		render( <AllProgramsTableCard /> );

		const row = screen.getByRole( 'row', { name: /product feed/i } );
		const editButton = getEditButton( row );
		const removeButton = getRemoveButton( row );

		expect( editButton ).not.toBeInTheDocument();
		expect( removeButton ).not.toBeInTheDocument();
	} );

	it( 'Should render the product feed row with a free daily budget text', () => {
		render( <AllProgramsTableCard /> );

		const row = screen.getByRole( 'row', { name: /product feed/i } );
		const budget = within( row ).getByRole( 'cell', { name: /free/i } );

		expect( budget ).toBeInTheDocument();
	} );

	it( 'Should render campaign rows', () => {
		mockCampaigns( pmaxCampaign, pmaxCampaignDisabled, shoppingCampaign );
		render( <AllProgramsTableCard /> );

		const rows = screen.getAllByRole( 'row', { name: /campaign/i } );
		expect( rows ).toHaveLength( 3 );
	} );

	it( 'Should render campaign rows with toggles in checked or unchecked state accordingly', () => {
		mockCampaigns( pmaxCampaign, pmaxCampaignDisabled );
		render( <AllProgramsTableCard /> );

		const rows = screen.getAllByRole( 'row', { name: /campaign/i } );
		const checkbox1 = within( rows[ 0 ] ).getByRole( 'checkbox' );
		const checkbox2 = within( rows[ 1 ] ).getByRole( 'checkbox' );

		expect( checkbox1 ).toBeChecked();
		expect( checkbox2 ).not.toBeChecked();
	} );

	it( 'Should call to formatAmount with the budget for each campaign rows', () => {
		const { formatAmount } = useAdsCurrency();

		mockCampaigns( pmaxCampaign, pmaxCampaignDisabled );
		render( <AllProgramsTableCard /> );

		expect( formatAmount ).toHaveBeenCalledWith(
			pmaxCampaign.amount,
			true
		);
		expect( formatAmount ).toHaveBeenCalledWith(
			pmaxCampaignDisabled.amount,
			true
		);
	} );

	it( 'Should render campaign rows with remove buttons', () => {
		mockCampaigns( pmaxCampaign, shoppingCampaign );
		render( <AllProgramsTableCard /> );

		const rows = screen.getAllByRole( 'row', { name: /campaign/i } );
		const button1 = getRemoveButton( rows[ 0 ] );
		const button2 = getRemoveButton( rows[ 1 ] );

		expect( button1 ).toBeEnabled();
		expect( button2 ).toBeEnabled();
	} );

	it( 'Should render the edit button for both enabled and disabled PMax campaign rows', () => {
		mockCampaigns( pmaxCampaign, pmaxCampaignDisabled );
		render( <AllProgramsTableCard /> );

		const rows = screen.getAllByRole( 'row', { name: /campaign/i } );
		const button1 = getEditButton( rows[ 0 ] );
		const button2 = getEditButton( rows[ 1 ] );

		expect( rows ).toHaveLength( 2 );
		expect( button1 ).toBeEnabled();
		expect( button2 ).toBeEnabled();
	} );

	it( 'Should render non-PMax campaign with an disabled edit button', () => {
		mockCampaigns( shoppingCampaign );
		render( <AllProgramsTableCard /> );

		const row = screen.getByRole( 'row', { name: /campaign/i } );
		const button = getEditButton( row );

		expect( button ).toBeDisabled();
	} );

	it( 'Should only attach the dedicated CSS class to the edit buttons of PMax campaign rows', () => {
		mockCampaigns( shoppingCampaign, pmaxCampaign );
		render( <AllProgramsTableCard /> );

		const rows = screen.getAllByRole( 'row', { name: /campaign/i } );
		const [ pmaxRow, shoppingRow ] = rows;
		const className = 'gla-campaign-edit-button';

		expect( getEditButton( shoppingRow ) ).not.toHaveClass( className );
		expect( getEditButton( pmaxRow ) ).toHaveClass( className );
	} );

	it( 'When there is no PMax campaign, should not render the campaign assets tour', () => {
		mockCampaigns( shoppingCampaign );
		render( <AllProgramsTableCard /> );

		const tour = screen.queryByRole( 'dialog', { name: 'tour' } );

		expect( tour ).not.toBeInTheDocument();
	} );

	it( 'When there is any PMax campaign, should render the campaign assets tour', () => {
		mockCampaigns( shoppingCampaign, pmaxCampaign );
		render( <AllProgramsTableCard /> );

		const tour = screen.getByRole( 'dialog', { name: 'tour' } );
		const expectedProps = expect.objectContaining( {
			referenceElementCssSelector: expect.stringMatching(
				/\.gla-campaign-edit-button\b/
			),
		} );

		expect( tour ).toBeInTheDocument();
		expect( CampaignAssetsTour ).toHaveBeenCalledWith( expectedProps, {} );
	} );

	it( 'Should render the Raise Budget Recommendation badge when there are recommendations', () => {
		mockCampaigns( shoppingCampaign, pmaxCampaign, pmaxCampaignDisabled );

		render( <AllProgramsTableCard /> );

		const rows = screen.getAllByRole( 'row', { name: /campaign/i } );

		expect( rows[ 0 ] ).toHaveTextContent( 'Budget recommendation' );
		expect( rows[ 1 ] ).toHaveTextContent( 'Budget recommendation' );
		expect( rows[ 2 ] ).not.toHaveTextContent( 'Budget recommendation' );
	} );

	describe( 'Sorting functionality', () => {
		beforeEach( () => {
			mockCampaigns(
				pmaxCampaign,
				pmaxCampaignDisabled,
				shoppingCampaign
			);
		} );

		it( 'should sort campaigns by id descending', () => {
			render( <AllProgramsTableCard /> );

			clickHeader( 'Program' );

			const rows = screen.getAllByRole( 'row', { name: /campaign/i } );
			const titles = rows.map(
				( row ) =>
					within( row ).getByRole( 'rowheader', {
						name: /campaign/i,
					} ).textContent
			);
			expect( titles ).toEqual( [
				'Shopping Campaign',
				'Disabled PMax Campaign Budget recommendation',
				'PMax Campaign Budget recommendation',
			] );
		} );

		it( 'should sort campaigns by id ascending', () => {
			render( <AllProgramsTableCard /> );

			clickHeader( 'Program', 2 );

			const rows = screen.getAllByRole( 'row', { name: /campaign/i } );
			const titles = rows.map(
				( row ) =>
					within( row ).getByRole( 'rowheader', {
						name: /campaign/i,
					} ).textContent
			);
			expect( titles ).toEqual( [
				'PMax Campaign Budget recommendation',
				'Disabled PMax Campaign Budget recommendation',
				'Shopping Campaign',
			] );
		} );

		it( 'should sort campaigns by dailyBudget descending', () => {
			render( <AllProgramsTableCard /> );
			clickHeader( 'Daily budget' );
			const rows = screen.getAllByRole( 'row', { name: /campaign/i } );
			const budgets = rows.map(
				( row ) => within( row ).getAllByRole( 'cell' )[ 1 ].textContent
			);

			expect( budgets ).toEqual( [ '50', '30', '20' ] );
		} );

		it( 'should sort campaigns by dailyBudget ascending', () => {
			render( <AllProgramsTableCard /> );
			clickHeader( 'Daily budget', 2 );
			const rows = screen.getAllByRole( 'row', { name: /campaign/i } );
			const budgets = rows.map(
				( row ) => within( row ).getAllByRole( 'cell' )[ 1 ].textContent
			);
			expect( budgets ).toEqual( [ '20', '30', '50' ] );
		} );

		it( 'should sort campaigns by enabled status descending', () => {
			render( <AllProgramsTableCard /> );
			clickHeader( 'Enabled' );
			const rows = screen.getAllByRole( 'row', { name: /campaign/i } );
			const statuses = rows.map( ( row ) => {
				const checkbox = within( row ).getByRole( 'checkbox' );
				return checkbox.checked;
			} );
			expect( statuses ).toEqual( [ true, true, false ] );
		} );

		it( 'should sort campaigns by enabled status ascending', () => {
			render( <AllProgramsTableCard /> );
			clickHeader( 'Enabled', 2 );
			const rows = screen.getAllByRole( 'row', { name: /campaign/i } );
			const statuses = rows.map( ( row ) => {
				const checkbox = within( row ).getByRole( 'checkbox' );
				return checkbox.checked;
			} );
			expect( statuses ).toEqual( [ false, true, true ] );
		} );

		it( 'should sort campaigns by country descending', () => {
			render( <AllProgramsTableCard /> );
			clickHeader( 'Country' );
			const rows = screen.getAllByRole( 'row', { name: /campaign/i } );
			const countries = rows.map( ( row ) => {
				const cell = within( row ).getAllByRole( 'cell' )[ 0 ];
				return cell.textContent;
			} );
			expect( countries ).toEqual( [
				'United States (US) + 1 more',
				'United States (US)',
				'Japan',
			] );
		} );

		it( 'should sort campaigns by country ascending', () => {
			render( <AllProgramsTableCard /> );
			clickHeader( 'Country', 2 );
			const rows = screen.getAllByRole( 'row', { name: /campaign/i } );
			const countries = rows.map( ( row ) => {
				const cell = within( row ).getAllByRole( 'cell' )[ 0 ];
				return cell.textContent;
			} );
			expect( countries ).toEqual( [
				'Japan',
				'United States (US)',
				'United States (US) + 1 more',
			] );
		} );
	} );
} );
