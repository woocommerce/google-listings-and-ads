/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */

import useCallbackOnceEffect from './useCallbackOnceEffect';

describe( 'useCallbackOnceEffect', () => {
	it( 'should only call the callback once when the condition is true', () => {
		const callbackMock = jest.fn();

		const { rerender } = renderHook(
			( { condition, callback, arg1 } ) =>
				useCallbackOnceEffect( condition, callback, arg1 ),
			{
				initialProps: {
					condition: false,
					callback: callbackMock,
					arg1: 1,
				},
			}
		);

		/**
		 * callback is not called because condition was false.
		 */
		expect( callbackMock ).toHaveBeenCalledTimes( 0 );

		/**
		 * set condition to true, callback should be called with arg1.
		 */
		rerender( {
			condition: true,
			callback: callbackMock,
			arg1: 2,
		} );
		expect( callbackMock ).toHaveBeenCalledTimes( 1 );
		expect( callbackMock ).toHaveBeenLastCalledWith( 2 );

		/**
		 * set condition to false, callback should not be called.
		 */
		rerender( {
			condition: false,
			callback: callbackMock,
			arg1: 3,
		} );
		expect( callbackMock ).toHaveBeenCalledTimes( 1 );

		/**
		 * set condition to true again, callback should not be called.
		 */
		rerender( {
			condition: true,
			callback: callbackMock,
			arg1: 4,
		} );
		expect( callbackMock ).toHaveBeenCalledTimes( 1 );
		expect( callbackMock ).toHaveBeenLastCalledWith( 2 );
	} );
} );
