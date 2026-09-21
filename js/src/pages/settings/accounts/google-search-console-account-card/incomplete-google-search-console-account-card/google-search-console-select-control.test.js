/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import GoogleSearchConsoleSelectControl from './google-search-console-select-control';

describe( 'GoogleSearchConsoleSelectControl', () => {
	it( 'renders a selectable option with no annotation for an exact match', () => {
		render(
			<GoogleSearchConsoleSelectControl
				properties={ [
					{
						siteUrl: 'https://example.com/',
						permissionLevel: 'siteOwner',
						covers_store_url: true,
						exact_match: true,
						usable: true,
					},
				] }
				onChange={ () => {} }
			/>
		);

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

	it( 'renders a usable but non-exact match as selectable, not disabled, with an explanation', () => {
		render(
			<GoogleSearchConsoleSelectControl
				properties={ [
					{
						siteUrl: 'sc-domain:example.com',
						permissionLevel: 'siteOwner',
						covers_store_url: true,
						exact_match: false,
						usable: true,
					},
				] }
				onChange={ () => {} }
			/>
		);

		const option = screen.getByRole( 'option', {
			name: "sc-domain:example.com (Not an exact match for this store's URL)",
		} );
		expect( option ).toBeInTheDocument();
		expect( option ).toBeEnabled();
	} );

	it( 'renders a non-covering match as disabled with an explanation', () => {
		render(
			<GoogleSearchConsoleSelectControl
				properties={ [
					{
						siteUrl: 'https://other-domain.com/',
						permissionLevel: 'siteUnverifiedUser',
						covers_store_url: false,
						usable: false,
					},
				] }
				onChange={ () => {} }
			/>
		);

		const option = screen.getByRole( 'option', {
			name: "https://other-domain.com/ (Doesn't cover this store's URL)",
		} );
		expect( option ).toBeInTheDocument();
		expect( option ).toBeDisabled();
	} );

	it( 'never lets autoSelectFirstOption pre-select a disabled property, regardless of list order', () => {
		const onChange = jest.fn();

		render(
			<GoogleSearchConsoleSelectControl
				properties={ [
					{
						siteUrl: 'https://other-domain.com/',
						permissionLevel: 'siteUnverifiedUser',
						covers_store_url: false,
						exact_match: false,
						usable: false,
					},
					{
						siteUrl: 'sc-domain:example.com',
						permissionLevel: 'siteOwner',
						covers_store_url: true,
						exact_match: false,
						usable: true,
					},
				] }
				autoSelectFirstOption
				onChange={ onChange }
			/>
		);

		expect( onChange ).toHaveBeenCalledWith( 'sc-domain:example.com' );
	} );

	it( 'renders a covering-but-unverified match as disabled with a different explanation', () => {
		render(
			<GoogleSearchConsoleSelectControl
				properties={ [
					{
						siteUrl: 'sc-domain:example.com',
						permissionLevel: 'siteUnverifiedUser',
						covers_store_url: true,
						usable: false,
					},
				] }
				onChange={ () => {} }
			/>
		);

		const option = screen.getByRole( 'option', {
			name: 'sc-domain:example.com (Not yet verified)',
		} );
		expect( option ).toBeInTheDocument();
		expect( option ).toBeDisabled();
	} );
} );
