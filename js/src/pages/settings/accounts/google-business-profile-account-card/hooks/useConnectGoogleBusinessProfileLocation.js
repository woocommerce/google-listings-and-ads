/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { API_NAMESPACE, ERROR_SLOTS } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import extractDetailedApiError from '~/utils/extractDetailedApiError';

/**
 * The connection error slot this hook records a failure in, and clears on success.
 *
 * @type {Array<string>}
 */
export const CONNECTION_ERROR_SLOTS = [
	ERROR_SLOTS.GOOGLE_BUSINESS_PROFILE_CONNECTION_ERROR_SLOT,
];

/**
 * @typedef {Object} ConnectGoogleBusinessProfileLocation
 * @property {boolean} isConnecting Whether a connect or refresh request is in flight.
 * @property {Function} connectLocation Connects the location with the given ID; resolves when the request completes.
 * @property {Function} retryStatusRefresh Retries only the status refresh, without repeating the connect request.
 */

/**
 * A hook to connect a Google Business Profile location: runs the connect request, refreshes the
 * connection state, and records a failure in the connection error slot.
 *
 * @return {ConnectGoogleBusinessProfileLocation} Whether a request is in flight, and the functions to connect a location or retry the status refresh.
 */
export default function useConnectGoogleBusinessProfileLocation() {
	const {
		fetchGoogleBusinessProfileAccount,
		receiveDetailedError,
		clearDetailedErrorBySlots,
	} = useAppDispatch();
	const [ isConnecting, setIsConnecting ] = useState( false );
	const [ fetchConnect ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/business-profile/locations`,
		method: 'POST',
	} );

	/**
	 * Records a connect/refresh failure in the connection error slot rather than a transient
	 * notice, since there's no page navigation here to otherwise lose track of the failure.
	 *
	 * @param {Error}   error                         The thrown error.
	 * @param {boolean} refreshFailedAfterConnecting Whether the connect request itself already
	 *                                                 succeeded, and only the status refresh failed
	 *                                                 -- read by `ConnectionErrorNotice` to retry
	 *                                                 only the refresh, since repeating the connect
	 *                                                 request for an already-connected location
	 *                                                 gets rejected by the backend.
	 */
	const recordConnectionError = async (
		error,
		refreshFailedAfterConnecting
	) => {
		const detailedError = await extractDetailedApiError( error );

		// Only trust a genuinely structured backend error for the message shown to the user —
		// `extractDetailedApiError`'s other branches synthesize a generic message (e.g. "An
		// unknown error occurred.") for network failures and other non-API-shaped errors, which
		// would otherwise shadow `ConnectionErrorNotice`'s own curated fallback copy. The slot is
		// still always marked (even with no message) — its presence is what surfaces the
		// failure at all.
		receiveDetailedError(
			ERROR_SLOTS.GOOGLE_BUSINESS_PROFILE_CONNECTION_ERROR_SLOT,
			{
				...( detailedError?.code === 'API_ERROR'
					? detailedError.data
					: {} ),
				refreshFailedAfterConnecting,
			}
		);
	};

	/**
	 * Connects a location and refreshes connection state.
	 *
	 * @param {string} locationId The ID of the location to connect.
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const connectLocation = async ( locationId ) => {
		setIsConnecting( true );
		try {
			await fetchConnect( { data: { id: locationId } } );

			try {
				await fetchGoogleBusinessProfileAccount();
				clearDetailedErrorBySlots( CONNECTION_ERROR_SLOTS );
			} catch ( error ) {
				await recordConnectionError( error, true );
			}
		} catch ( error ) {
			await recordConnectionError( error, false );
		} finally {
			setIsConnecting( false );
		}
	};

	/**
	 * Retries only the status refresh -- for when the connect request already succeeded and only
	 * confirming it failed.
	 *
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const retryStatusRefresh = async () => {
		setIsConnecting( true );
		try {
			await fetchGoogleBusinessProfileAccount();
			clearDetailedErrorBySlots( CONNECTION_ERROR_SLOTS );
		} catch ( error ) {
			await recordConnectionError( error, true );
		} finally {
			setIsConnecting( false );
		}
	};

	return {
		isConnecting,
		connectLocation,
		retryStatusRefresh,
	};
}
