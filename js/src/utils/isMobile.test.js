/**
 * Internal dependencies
 */
import { isWCIos, isWCAndroid } from '.~/utils/isMobileApp';

describe( 'isMobile', () => {
	let navigatorGetter;

	beforeEach( () => {
		// To initialize `pagePaths`.
		navigatorGetter = jest.spyOn( window.navigator, 'userAgent', 'get' );
	} );

	it( 'isWCIos', () => {
		navigatorGetter.mockReturnValue(
			'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 wc-ios/19.7.1'
		);

		expect( isWCIos() ).toBe( true );
	} );

	it( 'isWCAndroid', () => {
		navigatorGetter.mockReturnValue(
			'Mozilla/5.0 (iPhone; CPU Samsung ) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 wc-android/19.7.1'
		);

		expect( isWCAndroid() ).toBe( true );
	} );

	it( 'is not WCAndroid or isWCIos', () => {
		navigatorGetter.mockReturnValue(
			'Mozilla/5.0 (iPhone; CPU ) AppleWebKit/605.1.15 (KHTML, like Gecko)'
		);

		expect( isWCAndroid() ).toBe( false );
		expect( isWCIos() ).toBe( false );
	} );
} );
