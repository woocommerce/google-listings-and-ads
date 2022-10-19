/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useCountdown from './useCountdown';

describe( 'useCountdown', () => {
	it( 'initially should return `{ second: 0, callCount: 0, startCountdown: Function }`', () => {
		const { result } = renderHook( () => useCountdown() );

		expect( result.current.second ).toBe( 0 );
		expect( result.current.callCount ).toBe( 0 );
		expect( result.current.startCountdown ).toBeInstanceOf( Function );
	} );

	it( 'should record the calling count of `startCountdown()`', () => {
		const { result } = renderHook( () => useCountdown() );

		act( () => {
			result.current.startCountdown( 10 );
		} );

		expect( result.current.callCount ).toBe( 1 );

		act( () => {
			result.current.startCountdown( 20 );
		} );

		expect( result.current.callCount ).toBe( 2 );

		act( () => {
			result.current.startCountdown( 30 );
		} );

		expect( result.current.callCount ).toBe( 3 );
	} );

	describe( 'For the countdowns updated by interval timers', () => {
		let getTime;

		beforeEach( () => {
			jest.useFakeTimers();
			getTime = jest
				.spyOn( Date.prototype, 'getTime' )
				.mockName( 'Date.getTime' );
		} );

		afterEach( () => {
			jest.clearAllTimers();
			jest.useRealTimers();
		} );

		it( 'should start countdown 10 to 0 by calling `startCountdown( 10 )` and update the `second` property every second with the current remaining countdown.', () => {
			const { result } = renderHook( () => useCountdown() );

			act( () => {
				getTime.mockReturnValue( 0 );
				result.current.startCountdown( 10 );
			} );

			for (
				let second = 10, timestamp = 0;
				second >= 0;
				second -= 1, timestamp += 1000
			) {
				if ( timestamp > 0 ) {
					act( () => {
						getTime.mockReturnValue( timestamp );
						jest.runOnlyPendingTimers();
					} );
				}

				expect( result.current.second ).toBe( second );
			}
		} );

		it( 'even if the countdown time has exceeded the real elapsed time, the returned `second` value should be 0', () => {
			const { result } = renderHook( () => useCountdown() );

			act( () => {
				getTime.mockReturnValue( 0 );
				result.current.startCountdown( 1 );
			} );

			expect( result.current.second ).toBe( 1 );

			act( () => {
				getTime.mockReturnValue( 3000 );
				jest.runOnlyPendingTimers();
			} );

			expect( result.current.second ).toBe( 0 );
		} );

		it( 'should return corresponding countdown `second` and `callCount` by given `handle`', () => {
			const { result, rerender } = renderHook(
				( { handle } ) => useCountdown( handle ),
				{ initialProps: { handle: 'A' } }
			);

			// Start countdown for handle A.
			act( () => {
				getTime.mockReturnValue( 0 );
				result.current.startCountdown( 10 );
			} );

			expect( result.current.second ).toBe( 10 );

			// Switch to handle B and start countdown.
			rerender( { handle: 'B' } );
			act( () => {
				result.current.startCountdown( 5 );
			} );

			expect( result.current.second ).toBe( 5 );

			// Move forward 3 seconds.
			act( () => {
				getTime.mockReturnValue( 3000 );
				jest.runOnlyPendingTimers();
			} );

			expect( result.current.second ).toBe( 2 );

			// Start a new countdown for handle B.
			act( () => {
				result.current.startCountdown( 20 );
			} );

			expect( result.current.second ).toBe( 20 );
			expect( result.current.callCount ).toBe( 2 );

			// Switch back to handle A.
			rerender( { handle: 'A' } );

			expect( result.current.second ).toBe( 7 );
			expect( result.current.callCount ).toBe( 1 );
		} );
	} );
} );
