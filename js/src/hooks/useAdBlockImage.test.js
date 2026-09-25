/**
 * External dependencies
 */
import { renderHook, waitFor } from '@testing-library/react';
import { detectAnyAdblocker } from 'just-detect-adblock';

/**
 * Internal dependencies
 */
import useAdBlockImage from '~/hooks/useAdBlockImage';
import getProxiedImageUrl from '~/utils/getProxiedImageUrl';

jest.mock( 'just-detect-adblock', () => ( {
	detectAnyAdblocker: jest.fn(),
} ) );

jest.mock( '~/utils/getProxiedImageUrl', () =>
	jest.fn( ( url ) => `proxied:${ url }` )
);

const GOOGLE_AD_URL = 'https://tpc.googlesyndication.com/image.jpg';
const OTHER_URL = 'https://example.com/image.jpg';

describe( 'useAdBlockImage', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		// jsdom always reports offsetHeight as 0, which would trigger DOM bait detection.
		// Override to 1 so DOM check doesn't interfere with library-detection tests.
		Object.defineProperty( window.HTMLElement.prototype, 'offsetHeight', {
			configurable: true,
			get: jest.fn().mockReturnValue( 1 ),
		} );
		global.fetch = jest.fn().mockResolvedValue( {} );
	} );

	afterEach( () => {
		jest.restoreAllMocks();
	} );

	it( 'returns isLoading=true and isDetected=false on initial render', () => {
		detectAnyAdblocker.mockResolvedValue( false );
		const { result } = renderHook( () => useAdBlockImage() );

		expect( result.current.isLoading ).toBe( true );
		expect( result.current.isDetected ).toBe( false );
	} );

	it( 'sets isLoading=false after detection completes', async () => {
		detectAnyAdblocker.mockResolvedValue( false );
		const { result } = renderHook( () => useAdBlockImage() );

		await waitFor( () => expect( result.current.isLoading ).toBe( false ) );
	} );

	it( 'sets isDetected=true when library detects an adblocker', async () => {
		detectAnyAdblocker.mockResolvedValue( true );
		const { result } = renderHook( () => useAdBlockImage() );

		await waitFor( () => expect( result.current.isDetected ).toBe( true ) );
		expect( result.current.isLoading ).toBe( false );
	} );

	it( 'leaves isDetected=false when no adblocker is found', async () => {
		detectAnyAdblocker.mockResolvedValue( false );
		const { result } = renderHook( () => useAdBlockImage() );

		await waitFor( () => expect( result.current.isLoading ).toBe( false ) );
		expect( result.current.isDetected ).toBe( false );
	} );

	it( 'sets isDetected=true when detectAnyAdblocker throws', async () => {
		detectAnyAdblocker.mockRejectedValue( new Error( 'Detection failed' ) );
		const { result } = renderHook( () => useAdBlockImage() );

		await waitFor( () => expect( result.current.isDetected ).toBe( true ) );
		await waitFor( () => expect( result.current.isLoading ).toBe( false ) );
	} );

	describe( 'getDisplayImageUrl', () => {
		it( 'proxies Google Ads URLs when adblocker is detected', async () => {
			detectAnyAdblocker.mockResolvedValue( true );
			const { result } = renderHook( () => useAdBlockImage() );

			await waitFor( () =>
				expect( result.current.isDetected ).toBe( true )
			);

			const proxied = result.current.getDisplayImageUrl( GOOGLE_AD_URL );
			expect( getProxiedImageUrl ).toHaveBeenCalledWith( GOOGLE_AD_URL );
			expect( proxied ).toBe( `proxied:${ GOOGLE_AD_URL }` );
		} );

		it( 'returns original URL when no adblocker is detected', async () => {
			detectAnyAdblocker.mockResolvedValue( false );
			const { result } = renderHook( () => useAdBlockImage() );

			await waitFor( () =>
				expect( result.current.isLoading ).toBe( false )
			);

			expect( result.current.getDisplayImageUrl( GOOGLE_AD_URL ) ).toBe(
				GOOGLE_AD_URL
			);
		} );

		it( 'always returns original URL for non-Google-Ads domains', async () => {
			detectAnyAdblocker.mockResolvedValue( true );
			const { result } = renderHook( () => useAdBlockImage() );

			await waitFor( () =>
				expect( result.current.isDetected ).toBe( true )
			);

			expect( result.current.getDisplayImageUrl( OTHER_URL ) ).toBe(
				OTHER_URL
			);
			expect( getProxiedImageUrl ).not.toHaveBeenCalled();
		} );

		it( 'returns falsy values unchanged', async () => {
			detectAnyAdblocker.mockResolvedValue( true );
			const { result } = renderHook( () => useAdBlockImage() );

			await waitFor( () =>
				expect( result.current.isDetected ).toBe( true )
			);

			expect( result.current.getDisplayImageUrl( null ) ).toBeNull();
			expect(
				result.current.getDisplayImageUrl( undefined )
			).toBeUndefined();
		} );
	} );
} );
