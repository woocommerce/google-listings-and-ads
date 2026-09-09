/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useShouldResolveSearchConsoleProperty from './useShouldResolveSearchConsoleProperty';
import useGoogleSearchConsoleAccount from './useGoogleSearchConsoleAccount';
import useGoogleSearchConsoleProperties from './useGoogleSearchConsoleProperties';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';

jest.mock( './useGoogleSearchConsoleAccount' );
jest.mock( './useGoogleSearchConsoleProperties' );

const { INCOMPLETE, CONNECTED } = GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS;

describe( 'useShouldResolveSearchConsoleProperty hook', () => {
	it( 'reports undetermined while the account is still resolving', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: undefined,
			hasFinishedResolution: false,
		} );
		useGoogleSearchConsoleProperties.mockReturnValue( {
			properties: undefined,
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () =>
			useShouldResolveSearchConsoleProperty()
		);

		expect( result.current.hasDetermined ).toBe( false );
	} );

	it( 'does nothing once the status is not incomplete', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: CONNECTED },
			hasFinishedResolution: true,
		} );
		useGoogleSearchConsoleProperties.mockReturnValue( {
			properties: undefined,
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () =>
			useShouldResolveSearchConsoleProperty()
		);

		expect( result.current ).toEqual( {
			hasDetermined: true,
			shouldCreate: false,
			shouldAutoSelect: false,
			autoSelectSiteUrl: undefined,
		} );
	} );

	it( 'should create a new property when there are no candidates', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: INCOMPLETE },
			hasFinishedResolution: true,
		} );
		useGoogleSearchConsoleProperties.mockReturnValue( {
			properties: [],
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () =>
			useShouldResolveSearchConsoleProperty()
		);

		expect( result.current.shouldCreate ).toBe( true );
		expect( result.current.shouldAutoSelect ).toBe( false );
	} );

	it( 'should auto-select the one candidate when exactly one exists', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: INCOMPLETE },
			hasFinishedResolution: true,
		} );
		useGoogleSearchConsoleProperties.mockReturnValue( {
			properties: [ { siteUrl: 'https://example.com/' } ],
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () =>
			useShouldResolveSearchConsoleProperty()
		);

		expect( result.current.shouldCreate ).toBe( false );
		expect( result.current.shouldAutoSelect ).toBe( true );
		expect( result.current.autoSelectSiteUrl ).toBe(
			'https://example.com/'
		);
	} );

	it( 'does nothing for a genuine multi-match', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: { status: INCOMPLETE },
			hasFinishedResolution: true,
		} );
		useGoogleSearchConsoleProperties.mockReturnValue( {
			properties: [
				{ siteUrl: 'https://example.com/' },
				{ siteUrl: 'https://example.com/store/' },
			],
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () =>
			useShouldResolveSearchConsoleProperty()
		);

		expect( result.current.shouldCreate ).toBe( false );
		expect( result.current.shouldAutoSelect ).toBe( false );
	} );
} );
