jest.mock( '.~/hooks/useMCIssuesTotals', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useMCIssuesTotals' )
		.mockReturnValue( {
			totals: {
				account: 1,
				product: 2,
				total: 3,
			},
		} ),
} ) );

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import IssuesTableCard from '.~/product-feed/issues-table-card/index';
import useMCIssuesTotals from '.~/hooks/useMCIssuesTotals';

describe( 'Issues Table Card', () => {
	it( 'Rendering when there are issues', () => {
		const { getByText } = render( <IssuesTableCard /> );
		expect( getByText( 'Issues to resolve' ) ).toBeTruthy();
	} );

	it( 'Rendering the Issue Type Filter Navigation', () => {
		const { getByRole } = render( <IssuesTableCard /> );
		expect( getByRole( 'tablist' ) ).toBeTruthy();
	} );

	it( 'Rendering only one Issue table', () => {
		const { getAllByText } = render( <IssuesTableCard /> );
		expect( getAllByText( 'Type' ) ).toHaveLength( 1 );
	} );

	it( 'Not rendering when there are no issues', () => {
		useMCIssuesTotals.mockReturnValue( {
			totals: {
				account: 0,
				product: 0,
				total: 0,
			},
		} );
		render( <IssuesTableCard /> );
		expect( screen.queryByText( 'Issues to resolve' ) ).toBeFalsy();
	} );
} );
