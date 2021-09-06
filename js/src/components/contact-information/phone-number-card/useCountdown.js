/**
 * External dependencies
 */
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';

/**
 * @typedef {Object} Countdown
 * @property {number} second The current remaining seconds of countdown.
 * @property {number} callCount The calling count of `startCountdown` function.
 * @property {(countdownSecond: number) => void} startCountdown Start the countdown with the given starting seconds.
 */

/**
 * Get the corresponding countdown states and its start function by given `handle`.
 * The states would be updated every 1000 ms interval till counting down to 0.
 *
 * @param {string} handle The countdown handle.
 * @return {Countdown} The corresponding countdown states and its start function.
 */
export default function useCountdown( handle ) {
	const timerRef = useRef( {} );
	const [ second, setSecond ] = useState( 0 );

	timerRef.current.usingHandle = handle;
	timerRef.current[ handle ] = timerRef.current[ handle ] || { callCount: 0 };

	const startCountdown = useCallback(
		( countdownSecond ) => {
			setSecond( countdownSecond );

			const timer = timerRef.current[ handle ];
			const timeUp = new Date().getTime() + 1000 * countdownSecond;

			if ( timer.id ) {
				clearInterval( timer.id );
			}

			timer.updateSecond = () => {
				let remainingSecond = ( timeUp - new Date().getTime() ) / 1000;
				remainingSecond = Math.max( Math.round( remainingSecond ), 0 );

				if ( timerRef.current.usingHandle === handle ) {
					setSecond( remainingSecond );
				}

				if ( remainingSecond === 0 ) {
					clearInterval( timer.id );
				}
			};

			timer.id = setInterval( timer.updateSecond, 1000 );
			timer.callCount += 1;
		},
		[ handle ]
	);

	useEffect( () => {
		const { updateSecond } = timerRef.current[ handle ];
		if ( updateSecond ) {
			updateSecond();
		}
	}, [ handle ] );

	// Clean up timers.
	useEffect( () => {
		const timers = timerRef.current;
		return () => {
			Object.values( timers ).forEach( ( timer ) =>
				clearInterval( timer.id )
			);
		};
	}, [] );

	const { callCount } = timerRef.current[ handle ];
	return { second, callCount, startCountdown };
}
