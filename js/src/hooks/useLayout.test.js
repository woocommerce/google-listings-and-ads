/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useLayout from './useLayout';

describe( 'useLayout', () => {
	afterEach( () => {
		document.body.className = '';
	} );

	it( 'should attach the layout classes when mounting and detach them when unmounting', () => {
		const { unmount } = renderHook( () => useLayout( 'full-content' ) );

		expect( document.body.classList.contains( 'gla-full-content' ) ).toBe(
			true
		);

		unmount();

		expect( document.body.classList.contains( 'gla-full-content' ) ).toBe(
			false
		);
	} );

	it( 'should not detach classes that were already applied before mounting', () => {
		document.body.classList.add( 'gla-full-content' );

		const { unmount } = renderHook( () => useLayout( 'full-content' ) );
		unmount();

		expect( document.body.classList.contains( 'gla-full-content' ) ).toBe(
			true
		);
	} );

	it( 'should do nothing for an unknown layout name', () => {
		const { unmount } = renderHook( () => useLayout( 'not-a-layout' ) );

		expect( document.body.className ).toBe( '' );

		unmount();
	} );

	/**
	 * WooCommerce Admin sets `#wpbody`'s inline `margin-top` from the header's
	 * `clientHeight`. While a layout that hides the header is applied, that
	 * measurement is 0, and the stale value survives after the layout is
	 * detached — leaving the header overlapping page content (GOOWOO-255).
	 *
	 * Core recalculates on window resize, so the cleanup must dispatch one to
	 * let core re-measure the now-visible header.
	 */
	describe.each( [ 'full-page', 'full-content' ] )(
		'when the %s layout is detached',
		( layoutName ) => {
			it( 'should dispatch a resize event so the body margin is recalculated', () => {
				const onResize = jest.fn();
				window.addEventListener( 'resize', onResize );

				const { unmount } = renderHook( () => useLayout( layoutName ) );

				expect( onResize ).not.toHaveBeenCalled();

				unmount();

				expect( onResize ).toHaveBeenCalledTimes( 1 );

				window.removeEventListener( 'resize', onResize );
			} );
		}
	);

	it( 'should not dispatch a resize event for an unknown layout name', () => {
		const onResize = jest.fn();
		window.addEventListener( 'resize', onResize );

		const { unmount } = renderHook( () => useLayout( 'not-a-layout' ) );
		unmount();

		expect( onResize ).not.toHaveBeenCalled();

		window.removeEventListener( 'resize', onResize );
	} );
} );
