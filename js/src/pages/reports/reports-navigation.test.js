/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportsNavigation from './reports-navigation';

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getQuery: jest.fn(),
} ) );

describe( 'ReportsNavigation', () => {
	it( 'should list the Organic search tab third, after Programs and Products', () => {
		getQuery.mockReturnValue( {} );

		render( <ReportsNavigation /> );

		const tabs = screen.getAllByRole( 'tab' );
		expect( tabs.map( ( tab ) => tab.textContent.trim() ) ).toEqual( [
			'Programs',
			'Products',
			'Organic search',
		] );
		expect( tabs[ 2 ] ).toHaveAttribute(
			'href',
			expect.stringContaining( 'reportKey=search-console' )
		);
	} );

	it( 'should select the Organic search tab when it is the report in the URL', () => {
		getQuery.mockReturnValue( { reportKey: 'search-console' } );

		render( <ReportsNavigation /> );

		expect(
			screen.getByRole( 'tab', { name: 'Organic search' } )
		).toHaveAttribute( 'aria-selected', 'true' );
		expect(
			screen.getByRole( 'tab', { name: 'Programs' } )
		).toHaveAttribute( 'aria-selected', 'false' );
	} );
} );
