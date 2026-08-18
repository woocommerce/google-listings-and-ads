/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import GoogleSearchConsoleSelectControl from './google-search-console-select-control';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleAccount' )
);

describe( 'GoogleSearchConsoleSelectControl', () => {
	it( 'renders a selectable option for each covering property', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: {
				properties: [
					{
						url: 'https://example.com/',
						type: 'url_prefix',
						selectable: true,
					},
				],
			},
		} );

		render( <GoogleSearchConsoleSelectControl onChange={ () => {} } /> );

		const option = screen.getByRole( 'option', {
			name: 'https://example.com/',
		} );
		expect( option ).toBeInTheDocument();
		expect( option ).toBeEnabled();
		expect(
			screen.queryByRole( 'option', {
				name: 'Create a new property',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'renders non-covering properties as disabled with an explanation', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: {
				properties: [
					{
						url: 'https://other-domain.com/',
						type: 'domain',
						selectable: false,
						reason: "Doesn't cover this store's URL",
					},
				],
			},
		} );

		render( <GoogleSearchConsoleSelectControl onChange={ () => {} } /> );

		const option = screen.getByRole( 'option', {
			name: "https://other-domain.com/ (Doesn't cover this store's URL)",
		} );
		expect( option ).toBeInTheDocument();
		expect( option ).toBeDisabled();
	} );
} );
