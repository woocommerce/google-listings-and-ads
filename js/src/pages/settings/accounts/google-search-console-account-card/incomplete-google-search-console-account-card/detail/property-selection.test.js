/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import PropertySelection from './property-selection';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useGoogleSearchConsoleProperties from '~/hooks/useGoogleSearchConsoleProperties';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '~/data';

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleAccount' )
);
jest.mock( '~/hooks/useGoogleSearchConsoleProperties', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleProperties' )
);
jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);
jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

const { INCOMPLETE, ACTION_NEEDED } = GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS;

/**
 * Mocks `useGoogleSearchConsoleProperties`.
 *
 * @param {Object} overrides Fields to override on the default resolved payload.
 */
function mockProperties( overrides = {} ) {
	useGoogleSearchConsoleProperties.mockReturnValue( {
		properties: [],
		hasUsableProperty: false,
		hasExactMatch: false,
		hasFinishedResolution: true,
		...overrides,
	} );
}

describe( 'PropertySelection', () => {
	beforeEach( () => {
		jest.clearAllMocks();

		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: ACTION_NEEDED },
			hasFinishedResolution: true,
		} );
		mockProperties();

		useApiFetchCallback.mockReturnValue( [
			jest.fn().mockName( 'fetchProperty' ).mockResolvedValue(),
			{ loading: false },
		] );
		useDispatchCoreNotices.mockReturnValue( {
			createNotice: jest.fn().mockName( 'createNotice' ),
		} );
		useAppDispatch.mockReturnValue( {
			invalidateResolution: jest.fn().mockName( 'invalidateResolution' ),
		} );
	} );

	it( 'hides "Create new property" when the candidate list already has a usable exact match', () => {
		mockProperties( {
			properties: [
				{
					siteUrl: 'https://example.com/',
					usable: true,
					exact_match: true,
				},
			],
			hasUsableProperty: true,
			hasExactMatch: true,
		} );

		render( <PropertySelection /> );

		expect(
			screen.queryByRole( 'button', { name: 'Create new property' } )
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Save' } )
		).toBeInTheDocument();
	} );

	it( 'shows "Create new property" alongside the selector when no usable candidate is an exact match', () => {
		mockProperties( {
			properties: [
				{
					siteUrl: 'sc-domain:example.com',
					usable: true,
					exact_match: false,
				},
			],
			hasUsableProperty: true,
			hasExactMatch: false,
		} );

		render( <PropertySelection /> );

		expect(
			screen.getByRole( 'button', { name: 'Create new property' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Save' } )
		).toBeInTheDocument();
	} );

	it( 'shows "Create new property" alone when the account needs action and no usable candidate remains', () => {
		mockProperties( {
			properties: [],
			hasUsableProperty: false,
			hasExactMatch: false,
		} );

		render( <PropertySelection /> );

		expect(
			screen.getByRole( 'button', { name: 'Create new property' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save' } )
		).not.toBeInTheDocument();
	} );

	it( 'renders nothing when the account does not need action and there is no usable candidate', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: INCOMPLETE },
			hasFinishedResolution: true,
		} );
		mockProperties( {
			properties: [],
			hasUsableProperty: false,
			hasExactMatch: false,
		} );

		const { container } = render( <PropertySelection /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'shows a loading label while the candidate properties are still resolving', () => {
		mockProperties( { hasFinishedResolution: false } );

		render( <PropertySelection /> );

		expect(
			screen.getByText( 'Loading Google Search Console properties…' )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Create new property' } )
		).not.toBeInTheDocument();
	} );
} );
