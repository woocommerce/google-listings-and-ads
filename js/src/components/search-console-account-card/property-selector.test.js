/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import PropertySelector from './property-selector';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useExistingSearchConsoleProperties from '~/hooks/useExistingSearchConsoleProperties';
import { useAppDispatch } from '~/data';

jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);

jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

jest.mock( '~/hooks/useExistingSearchConsoleProperties', () =>
	jest.fn().mockName( 'useExistingSearchConsoleProperties' )
);

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

describe( 'PropertySelector', () => {
	let fetchSelectProperty;
	let createNotice;
	let invalidateResolution;

	beforeEach( () => {
		fetchSelectProperty = jest.fn().mockName( 'fetchSelectProperty' );
		useApiFetchCallback.mockReturnValue( [
			fetchSelectProperty,
			{ loading: false },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		invalidateResolution = jest.fn().mockName( 'invalidateResolution' );
		useAppDispatch.mockReturnValue( { invalidateResolution } );
	} );

	it( 'renders no selector when there is a single (auto-resolved) property', () => {
		useExistingSearchConsoleProperties.mockReturnValue( {
			data: [ { url: 'https://example.com/', selectable: true } ],
			hasFinishedResolution: true,
		} );

		render( <PropertySelector /> );

		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
		expect(
			screen.getByText( 'Setting up your Search Console property…' )
		).toBeInTheDocument();
	} );

	it( 'renders the selector when there are multiple candidate properties', () => {
		useExistingSearchConsoleProperties.mockReturnValue( {
			data: [
				{ url: 'https://a.example.com/', selectable: true },
				{ url: 'https://b.example.com/', selectable: true },
			],
			hasFinishedResolution: true,
		} );

		render( <PropertySelector /> );

		expect( screen.getByRole( 'combobox' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Continue' } )
		).toBeDisabled();
	} );

	it( 'submits the selected property and invalidates resolution on success', async () => {
		const user = userEvent.setup();
		fetchSelectProperty.mockResolvedValue( {} );
		useExistingSearchConsoleProperties.mockReturnValue( {
			data: [
				{ url: 'https://a.example.com/', selectable: true },
				{ url: 'https://b.example.com/', selectable: true },
			],
			hasFinishedResolution: true,
		} );

		render( <PropertySelector /> );

		await user.selectOptions(
			screen.getByRole( 'combobox' ),
			'https://a.example.com/'
		);
		await user.click( screen.getByRole( 'button', { name: 'Continue' } ) );

		expect( fetchSelectProperty ).toHaveBeenCalledWith( {
			data: { url: 'https://a.example.com/' },
		} );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getSearchConsoleAccount',
			[]
		);
	} );

	it( 'shows an error notice when the selection request fails', async () => {
		const user = userEvent.setup();
		fetchSelectProperty.mockRejectedValue( new Error( 'failed' ) );
		useExistingSearchConsoleProperties.mockReturnValue( {
			data: [
				{ url: 'https://a.example.com/', selectable: true },
				{ url: 'https://b.example.com/', selectable: true },
			],
			hasFinishedResolution: true,
		} );

		render( <PropertySelector /> );

		await user.selectOptions(
			screen.getByRole( 'combobox' ),
			'https://a.example.com/'
		);
		await user.click( screen.getByRole( 'button', { name: 'Continue' } ) );

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'Unable to select' )
		);
	} );
} );
