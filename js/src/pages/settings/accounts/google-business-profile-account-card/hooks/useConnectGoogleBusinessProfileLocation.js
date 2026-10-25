/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { API_NAMESPACE, ERROR_SLOTS } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDetailedErrorBySlots from '~/hooks/useDetailedErrorBySlots';
import extractDetailedApiError from '~/utils/extractDetailedApiError';

/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileLocation} GoogleBusinessProfileLocation
 */

/**
 * The connection error slot this hook reads from and clears.
 *
 * @type {Array<string>}
 */
export const CONNECTION_ERROR_SLOTS = [
	ERROR_SLOTS.GOOGLE_BUSINESS_PROFILE_CONNECTION_ERROR_SLOT,
];

/**
 * A hook encapsulating the Google Business Profile connect flow: the picked location,
 * auto-selecting the only candidate when there's exactly one, the connect request itself, and
 * surfacing a failure through the connection error slot.
 *
 * @param {GoogleBusinessProfileLocation[]|null} [locations] The candidate locations.
 * @return {{
 *   locationId: (string|undefined),
 *   setLocationId: (locationId: string) => void,
 *   isConnecting: boolean,
 *   hasConnectionError: boolean,
 *   handleConnectClick: () => Promise<void>,
 * }} The connect flow's state and actions.
 */
export default function useConnectGoogleBusinessProfileLocation( locations ) {
	const {
		fetchGoogleBusinessProfileAccount,
		receiveDetailedError,
		clearDetailedErrorBySlots,
	} = useAppDispatch();
	const [ locationId, setLocationId ] = useState();
	const [ connectionError ] = useDetailedErrorBySlots(
		CONNECTION_ERROR_SLOTS
	);
	const hasConnectionError = Boolean( connectionError );
	const [ isConnecting, setIsConnecting ] = useState( false );
	const [ fetchConnect ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/business-profile/locations`,
		method: 'POST',
		data: {
			id: locationId,
		},
	} );

	// With only one candidate there's nothing to pick — auto-select it so "Connect" enables
	// without showing a selector that only ever has one option.
	useEffect( () => {
		if ( locations?.length !== 1 ) {
			return;
		}

		setLocationId( locations[ 0 ].id );
	}, [ locations ] );

	/**
	 * Handles the "Connect" and "Save" button clicks: connects the picked location and refreshes
	 * connection state. A failure is recorded in the connection error slot rather than a transient
	 * notice, since there's no page navigation here to otherwise lose track of the failure.
	 *
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const handleConnectClick = async () => {
		setIsConnecting( true );
		try {
			await fetchConnect();
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
		locationId,
		setLocationId,
		isConnecting,
		hasConnectionError,
		handleConnectClick,
	};
}
