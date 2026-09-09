/**
 * External dependencies
 */
import { renderHook, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useAutoResolveSearchConsoleProperty from './useAutoResolveSearchConsoleProperty';
import useShouldResolveSearchConsoleProperty from './useShouldResolveSearchConsoleProperty';
import useResolveSearchConsoleProperty from './useResolveSearchConsoleProperty';

jest.mock( './useShouldResolveSearchConsoleProperty' );
jest.mock( './useResolveSearchConsoleProperty' );

describe( 'useAutoResolveSearchConsoleProperty hook', () => {
	let resolveProperty;

	beforeEach( () => {
		resolveProperty = jest.fn( () => Promise.resolve() );
		useResolveSearchConsoleProperty.mockReturnValue( [
			resolveProperty,
			{ loading: false },
		] );
	} );

	it( 'auto-creates a property when there are no candidates', async () => {
		useShouldResolveSearchConsoleProperty.mockReturnValue( {
			hasDetermined: true,
			shouldCreate: true,
			shouldAutoSelect: false,
			autoSelectSiteUrl: undefined,
		} );

		const { result } = renderHook( () =>
			useAutoResolveSearchConsoleProperty()
		);

		await waitFor( () =>
			expect( result.current.justResolved ).toBe( true )
		);

		expect( resolveProperty ).toHaveBeenCalledTimes( 1 );
		expect( resolveProperty ).toHaveBeenCalledWith( undefined );
	} );

	it( 'auto-selects the one candidate when exactly one exists', async () => {
		useShouldResolveSearchConsoleProperty.mockReturnValue( {
			hasDetermined: true,
			shouldCreate: false,
			shouldAutoSelect: true,
			autoSelectSiteUrl: 'https://example.com/',
		} );

		const { result } = renderHook( () =>
			useAutoResolveSearchConsoleProperty()
		);

		await waitFor( () =>
			expect( result.current.justResolved ).toBe( true )
		);

		expect( resolveProperty ).toHaveBeenCalledWith(
			'https://example.com/'
		);
	} );

	it( 'does nothing for a genuine multi-match', () => {
		useShouldResolveSearchConsoleProperty.mockReturnValue( {
			hasDetermined: true,
			shouldCreate: false,
			shouldAutoSelect: false,
			autoSelectSiteUrl: undefined,
		} );

		const { result } = renderHook( () =>
			useAutoResolveSearchConsoleProperty()
		);

		expect( result.current.justResolved ).toBe( false );
		expect( resolveProperty ).not.toHaveBeenCalled();
	} );

	it( 'does not fire while the decision is still being determined', () => {
		useShouldResolveSearchConsoleProperty.mockReturnValue( {
			hasDetermined: false,
			shouldCreate: false,
			shouldAutoSelect: false,
			autoSelectSiteUrl: undefined,
		} );

		const { result } = renderHook( () =>
			useAutoResolveSearchConsoleProperty()
		);

		expect( result.current.hasDetermined ).toBe( false );
		expect( resolveProperty ).not.toHaveBeenCalled();
	} );
} );
