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
 * A hook to connect a Google Business Profile location: runs the connect request, refreshes the
 * connection state, and records a failure in the connection error slot.
 *
 * @return {{
 *   isConnecting: boolean,
 *   connectLocation: (locationId: string) => Promise<void>,
 * }} Whether a connect request is in flight, and the function connecting a location.
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
	 * Connects a location and refreshes connection state. A failure is recorded in the connection
	 * error slot rather than a transient notice, since there's no page navigation here to otherwise
	 * lose track of the failure.
	 *
	 * @param {string} locationId The ID of the location to connect.
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const connectLocation = async ( locationId ) => {
		setIsConnecting( true );
		try {
			await fetchConnect( { data: { id: locationId } } );
			await fetchGoogleBusinessProfileAccount();
			clearDetailedErrorBySlots( CONNECTION_ERROR_SLOTS );
		} catch ( error ) {
			const detailedError = await extractDetailedApiError( error );

			// Only trust a genuinely structured backend error for the message shown to the user —
			// `extractDetailedApiError`'s other branches synthesize a generic message (e.g. "An
			// unknown error occurred.") for network failures and other non-API-shaped errors, which
			// would otherwise shadow `ConnectionErrorNotice`'s own curated fallback copy. The slot is
			// still always marked (even with no message) — its presence is what surfaces the
			// failure at all.
			receiveDetailedError(
				ERROR_SLOTS.GOOGLE_BUSINESS_PROFILE_CONNECTION_ERROR_SLOT,
				detailedError?.code === 'API_ERROR' ? detailedError.data : {}
			);
		} finally {
			setIsConnecting( false );
		}
	};

	return {
		isConnecting,
		connectLocation,
	};
}
