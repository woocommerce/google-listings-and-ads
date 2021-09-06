/**
 * External dependencies
 */
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';

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
