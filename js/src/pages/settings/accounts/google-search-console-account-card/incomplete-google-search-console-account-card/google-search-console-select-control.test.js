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
	it( 'renders a selectable option for each usable match', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: {
				matches: [
					{
						siteUrl: 'https://example.com/',
						permissionLevel: 'siteOwner',
						covers: true,
						usable: true,
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

	it( 'renders a non-covering match as disabled with an explanation', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: {
				matches: [
					{
						siteUrl: 'https://other-domain.com/',
						permissionLevel: 'siteUnverifiedUser',
						covers: false,
						usable: false,
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

	it( 'renders a covering-but-unverified match as disabled with a different explanation', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: {
				matches: [
					{
						siteUrl: 'sc-domain:example.com',
						permissionLevel: 'siteUnverifiedUser',
						covers: true,
						usable: false,
					},
				],
			},
		} );

		render( <GoogleSearchConsoleSelectControl onChange={ () => {} } /> );

		const option = screen.getByRole( 'option', {
			name: 'sc-domain:example.com (Not yet verified)',
		} );
		expect( option ).toBeInTheDocument();
		expect( option ).toBeDisabled();
	} );
} );
