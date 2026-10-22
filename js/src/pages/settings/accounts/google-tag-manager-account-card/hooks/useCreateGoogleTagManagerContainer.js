/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	CREATE_CONTAINER_PERMISSION_DENIED_MESSAGE,
	CREATED_NOT_CONNECTED_MESSAGE,
} from '../constants';
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { resolveErrorMessage } from '~/utils/handleError';
import useRefreshGoogleTagManagerConnection from './useRefreshGoogleTagManagerConnection';

/**
 * @typedef {Object} CreateContainerError
 * @property {string} message The message to show the merchant, naming the cause when it's known.
 * @property {boolean} canRetry Whether trying again can help. Neither a missing scope nor a refusal
 * of permission can be fixed by trying again.
 */

const NON_RETRYABLE_REASONS = [ 'insufficient_scope', 'permission_denied' ];

const MESSAGE_BY_REASON = {
	insufficient_scope: __(
		"Your Google connection doesn't allow creating Google Tag Manager containers.",
		'google-listings-and-ads'
	),
	permission_denied: CREATE_CONTAINER_PERMISSION_DENIED_MESSAGE,
	quota_exceeded: __(
		'Google Tag Manager has reached its request limit. Please try again later.',
		'google-listings-and-ads'
	),
};

/**
 * Turns a failed create request into the error shown to the merchant.
 *
 * @param {Object} error The error thrown by the create request.
 * @return {CreateContainerError} The error to show.
 */
function toCreateContainerError( error ) {
	const reason = error?.data?.reason;

	return {
		message:
			MESSAGE_BY_REASON[ reason ] ??
			resolveErrorMessage(
				error,
				undefined,
				__(
					'Unable to create the Google Tag Manager container. Please try again.',
					'google-listings-and-ads'
				)
			),
		canRetry: ! NON_RETRYABLE_REASONS.includes( reason ),
	};
}

/**
 * A hook returning a function that creates a Google Tag Manager container and connects it, then
 * refreshes the connection so the card switches to its connected state.
 *
 * A successful create always connects the container: the backend reports a failed connect as an
 * error, and a retry connects the container it already created instead of making another. So once
 * the create succeeds, only a failed refresh is reported, as `CREATED_NOT_CONNECTED_MESSAGE`.
 *
 * @return {Array} `[ createContainer, { loading, error } ]`: the function that creates a container
 * with the given name; `loading`, true while the create request or the refresh is in progress; and
 * `error`, the `CreateContainerError` from the last attempt, or `null`.
 */
export default function useCreateGoogleTagManagerContainer() {
	const [ error, setError ] = useState( null );
	const { refreshConnection, loading: isRefreshing } =
		useRefreshGoogleTagManagerConnection();
	const [ fetchCreateContainer, { loading: isCreating } ] =
		useApiFetchCallback( {
			path: `${ API_NAMESPACE }/tag-manager/containers`,
			method: 'POST',
		} );

	const createContainer = useCallback(
		async ( name ) => {
			setError( null );

			try {
				await fetchCreateContainer( { data: { name } } );
			} catch ( createError ) {
				// A container is already connected, so only the card has to catch up with it.
				if ( createError?.data?.reason !== 'already_connected' ) {
					setError( toCreateContainerError( createError ) );
					return;
				}
			}

			if ( ! ( await refreshConnection() ) ) {
				setError( {
					message: CREATED_NOT_CONNECTED_MESSAGE,
					canRetry: true,
				} );
			}
		},
		[ fetchCreateContainer, refreshConnection ]
	);

	return [ createContainer, { loading: isCreating || isRefreshing, error } ];
}
