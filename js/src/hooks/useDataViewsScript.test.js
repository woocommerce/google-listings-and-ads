/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';

const SCRIPT_URL = 'https://example.test/dataviews.js';

// Pin `@wordpress/element` to the same React instance that
// `@testing-library/react` uses, so that re-importing the hook via
// `jest.isolateModules` (to reset its module-level singleton) doesn't
// produce a second React copy and trigger "Invalid hook call" errors.
// `mock`-prefixed identifiers are exempt from jest.mock's hoist guard.
const mockReact = jest.requireActual( 'react' );
jest.mock( '@wordpress/element', () => mockReact );

let mockGlaData;

jest.mock( '~/constants', () => ( {
	get glaData() {
		return mockGlaData;
	},
} ) );

const flushPromises = () =>
	new Promise( ( resolve ) => setImmediate( resolve ) );

const setReadyGlobal = () => {
	window.wp = {
		dataviews: { filterSortAndPaginate: jest.fn() },
	};
};

const loadHook = () => {
	let hook;
	jest.isolateModules( () => {
		hook = require( './useDataViewsScript' ).default;
	} );
	return hook;
};

describe( 'useDataViewsScript', () => {
	let appendedScripts;
	let appendChildSpy;

	beforeEach( () => {
		mockGlaData = { dataViewsScriptUrl: SCRIPT_URL };
		appendedScripts = [];

		appendChildSpy = jest
			.spyOn( document.head, 'appendChild' )
			.mockImplementation( ( node ) => {
				appendedScripts.push( node );
				return node;
			} );
	} );

	afterEach( () => {
		delete window.wp;
		appendChildSpy.mockRestore();
	} );

	it( 'returns "ready" synchronously when window.wp.dataviews is already available', () => {
		setReadyGlobal();
		const useDataViewsScript = loadHook();

		const { result } = renderHook( () => useDataViewsScript() );

		expect( result.current ).toBe( 'ready' );
		expect( appendedScripts ).toHaveLength( 0 );
	} );

	it( 'injects exactly one <script> tag for two concurrent hook instances', () => {
		const useDataViewsScript = loadHook();

		renderHook( () => useDataViewsScript() );
		renderHook( () => useDataViewsScript() );

		expect( appendedScripts ).toHaveLength( 1 );
		expect( appendedScripts[ 0 ].src ).toBe( SCRIPT_URL );
	} );

	it( 'resolves both concurrent instances to "ready" after a single onload', async () => {
		const useDataViewsScript = loadHook();

		const a = renderHook( () => useDataViewsScript() );
		const b = renderHook( () => useDataViewsScript() );

		expect( a.result.current ).toBe( 'loading' );
		expect( b.result.current ).toBe( 'loading' );

		await act( async () => {
			setReadyGlobal();
			appendedScripts[ 0 ].onload();
			await flushPromises();
		} );

		expect( a.result.current ).toBe( 'ready' );
		expect( b.result.current ).toBe( 'ready' );
	} );

	it( 'resolves to "failed" on onerror and allows a subsequent mount to retry', async () => {
		const useDataViewsScript = loadHook();

		const first = renderHook( () => useDataViewsScript() );
		expect( first.result.current ).toBe( 'loading' );

		await act( async () => {
			appendedScripts[ 0 ].onerror();
			await flushPromises();
		} );

		expect( first.result.current ).toBe( 'failed' );

		// A subsequent mount should attempt to load again, not return cached failure.
		renderHook( () => useDataViewsScript() );
		expect( appendedScripts ).toHaveLength( 2 );
	} );

	// `@wordpress/jest-console` (preset) auto-fails the test if any
	// `console.error` is emitted, e.g. React's "setState after unmount"
	// warning, so this test relies on that side effect.
	it( 'does not warn when onload fires after unmount', async () => {
		const useDataViewsScript = loadHook();

		const { result, unmount } = renderHook( () => useDataViewsScript() );
		expect( result.current ).toBe( 'loading' );

		unmount();

		await act( async () => {
			setReadyGlobal();
			appendedScripts[ 0 ].onload();
			await flushPromises();
		} );
	} );

	it( 'returns "failed" when no script URL is configured', async () => {
		mockGlaData = {};
		const useDataViewsScript = loadHook();

		const { result } = renderHook( () => useDataViewsScript() );

		await act( async () => {
			await flushPromises();
		} );

		expect( result.current ).toBe( 'failed' );
		expect( appendedScripts ).toHaveLength( 0 );
	} );
} );
