/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, within, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AllProgramsTableCard from './';
import CampaignAssetsTour from '.~/components/tours/campaign-assets-tour';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import useAdsCurrency from '.~/hooks/useAdsCurrency';

jest.mock( '.~/components/tours/campaign-assets-tour', () =>
	jest
		.fn()
		.mockReturnValue( <div role="dialog" aria-label="tour" /> )
		.mockName( 'CampaignAssetsTour' )
);

jest.mock( '.~/hooks/useCountryKeyNameMap', () =>
	jest
		.fn()
		.mockReturnValue( { US: 'United States (US)', JP: 'Japan' } )
		.mockName( 'useCountryKeyNameMap' )
);

jest.mock( '.~/hooks/useAdsCurrency', () =>
	jest.fn().mockReturnValue( {
		formatAmount: jest.fn().mockName( 'formatAmount' ),
	} )
);

jest.mock( '.~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest
		.fn()
		.mockReturnValue( { data: [ 'US', 'JP' ] } )
		.mockName( 'useTargetAudienceFinalCountryCodes' )
);

jest.mock( '.~/hooks/useAdsCampaigns', () =>
	jest.fn().mockName( 'useAdsCampaigns' )
);

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

	let mockCampaigns;

	beforeEach( () => {
		let mockedCampaigns = [];

		useAdsCampaigns.mockImplementation( () => {
			return { data: mockedCampaigns };
		} );

		mockCampaigns = ( ...campaigns ) => {
			mockedCampaigns = campaigns;
		};
	} );

	it( 'Should render the free listings row with a checked toggle in the disabled state', () => {
		render( <AllProgramsTableCard /> );

		const row = screen.getByRole( 'row', { name: /free listings/i } );
		const checkbox = within( row ).getByRole( 'checkbox' );

		expect( row ).toBeInTheDocument();
		expect( checkbox ).toBeChecked();
		expect( checkbox ).toBeDisabled();
	} );

	it( 'Should render the free listings row without the remove button', () => {
		render( <AllProgramsTableCard /> );

		const row = screen.getByRole( 'row', { name: /free listings/i } );
		const button = getRemoveButton( row );

		expect( button ).not.toBeInTheDocument();
	} );

	it( 'Should render the free listings row with a free daily budget text', () => {
		render( <AllProgramsTableCard /> );

		const row = screen.getByRole( 'row', { name: /free listings/i } );
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

	it( 'Should render the free listings and PMax campaign rows with edit buttons', () => {
		mockCampaigns( pmaxCampaign );
		render( <AllProgramsTableCard /> );

		const freeRow = screen.getByRole( 'row', { name: /free listings/i } );
		const pmaxRow = screen.getByRole( 'row', { name: /campaign/i } );
		const freeButton = getEditButton( freeRow );
		const pmaxButton = getEditButton( pmaxRow );

		expect( freeButton ).toBeEnabled();
		expect( pmaxButton ).toBeEnabled();
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

		const rows = screen.getAllByRole( 'row', {
			name: /free listings|campaign/i,
		} );
		const [ freeRow, shoppingRow, pmaxRow ] = rows;
		const className = 'gla-campaign-edit-button';

		expect( getEditButton( freeRow ) ).not.toHaveClass( className );
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
} );
