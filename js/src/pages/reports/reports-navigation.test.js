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
import useIsOrganicSearchReportAvailable from './search-console/hooks/useIsOrganicSearchReportAvailable';

jest.mock( './search-console/hooks/useIsOrganicSearchReportAvailable', () =>
	jest.fn().mockName( 'useIsOrganicSearchReportAvailable' )
);

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getQuery: jest.fn(),
} ) );

const getTabTitles = () => {
	return screen
		.getAllByRole( 'tab' )
		.map( ( tab ) => tab.textContent.trim() );
};

describe( 'ReportsNavigation', () => {
	it( 'should not list the Organic search tab when Search Console is not connected', () => {
		useIsOrganicSearchReportAvailable.mockReturnValue( false );
		getQuery.mockReturnValue( {} );

		render( <ReportsNavigation /> );

		expect( getTabTitles() ).toEqual( [ 'Programs', 'Products' ] );
	} );

	it( 'should list the Organic search tab third, after Programs and Products, when Search Console is connected', () => {
		useIsOrganicSearchReportAvailable.mockReturnValue( true );
		getQuery.mockReturnValue( {} );

		render( <ReportsNavigation /> );

		expect( getTabTitles() ).toEqual( [
			'Programs',
			'Products',
			'Organic search',
		] );
		expect(
			screen.getByRole( 'tab', { name: 'Organic search' } )
		).toHaveAttribute(
			'href',
			expect.stringContaining( 'reportKey=search-console' )
		);
	} );

	it( 'should select the Organic search tab when it is the report in the URL', () => {
		useIsOrganicSearchReportAvailable.mockReturnValue( true );
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
