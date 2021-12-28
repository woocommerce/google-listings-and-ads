jest.mock( '.~/hooks/useMCIssues', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useMCIssues' )
		.mockReturnValue( {
			account: {
				data: {
					total: 0,
				},
			},
			product: {
				data: {
					total: 0,
				},
			},
		} ),
} ) );

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import IssuesTypeNavigation from '.~/product-feed/issues-table-card/issues-type-navigation';
import useMCIssues from '.~/hooks/useMCIssues';

describe( 'Issues Type Navigation', () => {
	describe( 'When totals are defined', () => {
		beforeEach( () => {
			render( <IssuesTypeNavigation /> );
		} );

		it( 'Rendering Account and Product Tabs with the correct number of issues', () => {
			const tabs = screen.queryAllByRole( `tab` );
			expect( tabs ).toHaveLength( 2 );
			expect( screen.queryByText( `Account Issues (0)` ) ).toBeTruthy();
			expect( screen.queryByText( `Product Issues (0)` ) ).toBeTruthy();
		} );

		it( 'Showing the right URL paths', () => {
			expect(
				screen.queryByText( `Account Issues (0)` )
			).toHaveAttribute(
				'href',
				expect.stringContaining( 'issueType=account' )
			);

			expect(
				screen.queryByText( `Product Issues (0)` )
			).toHaveAttribute(
				'href',
				expect.stringContaining( 'issueType=product' )
			);
		} );
	} );

	describe( 'When totals are undefined', () => {
		it( 'Not Rendering the totals', () => {
			useMCIssues.mockReturnValue( {
				account: {
					data: {
						total: undefined,
					},
				},
				product: {
					data: {
						total: undefined,
					},
				},
			} );
			render( <IssuesTypeNavigation /> );
			expect( screen.queryByText( `Account Issues` ) ).toBeTruthy();
			expect( screen.queryByText( `Product Issues` ) ).toBeTruthy();
		} );
	} );
} );
