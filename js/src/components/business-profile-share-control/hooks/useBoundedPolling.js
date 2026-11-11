/**
 * External dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * @typedef {Object} BoundedPolling
 * @property {() => void} start Starts a new round of checks, dropping the previous round's data.
 * @property {*} data The latest response data in the current round, or `undefined` before the first one.
 * @property {boolean} hasReachedMaxAttempts Whether the current round stopped because its checks ran out.
 */

/**
 * A hook to check a REST path on an interval until the response says it's done, or until a set
 * number of checks has been made. A failed check counts as one of them, and checking continues.
 *
 * @param {Object} props Hook props.
 * @param {string|null} props.path The REST path to check.
 * @param {number} props.intervalSeconds Seconds to wait between checks.
 * @param {number} props.maxAttempts The most checks per round.
 * @param {(data: *) => boolean} props.isDone Returns whether checking can stop, given a response.
 * @return {BoundedPolling} The trigger for a round of checks, and its state.
 */
export default function useBoundedPolling( {
	path,
	intervalSeconds,
	maxAttempts,
	isDone,
} ) {
	const [ data, setData ] = useState();
	const [ hasReachedMaxAttempts, setHasReachedMaxAttempts ] =
		useState( false );

	// Each round gets its own ID, so a response from an earlier round, or after unmounting,
	// is ignored.
	const roundRef = useRef( 0 );
	const timeoutRef = useRef();
	const isDoneRef = useRef( isDone );
	isDoneRef.current = isDone;

	useEffect( () => {
		return () => {
			roundRef.current += 1;
			clearTimeout( timeoutRef.current );
		};
	}, [] );

	const start = useCallback( () => {
		roundRef.current += 1;
		const round = roundRef.current;

		clearTimeout( timeoutRef.current );
		setData( undefined );
		setHasReachedMaxAttempts( false );

		const check = async ( attempt ) => {
			let response;

			try {
				response = await apiFetch( { path } );
			} catch {
				// A failed check counts as an attempt; the next one may succeed.
			}

			if ( round !== roundRef.current ) {
				return;
			}

			if ( response !== undefined ) {
				setData( response );

				if ( isDoneRef.current( response ) ) {
					return;
				}
			}

			if ( attempt >= maxAttempts ) {
				setHasReachedMaxAttempts( true );
				return;
			}

			timeoutRef.current = setTimeout( () => {
				check( attempt + 1 );
			}, intervalSeconds * 1000 );
		};

		check( 1 );
	}, [ path, intervalSeconds, maxAttempts ] );

	return { start, data, hasReachedMaxAttempts };
}
