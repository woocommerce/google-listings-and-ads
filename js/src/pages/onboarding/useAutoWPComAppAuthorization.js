/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';
import { useRef, useReducer } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useJetpackAccount from '~/hooks/useJetpackAccount';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { recordGlaEvent } from '~/utils/tracks';

/**
 * Start the WPCOM app authorization process to enable the product sync (API Pull).
 *
 * @event gla_enable_product_sync
 * @property {string} page Indicates the page where this event happened
 * @property {string} context Indicates the origin that triggered this event
 */

/**
 * Hook to automatically redirect the user to WPCOM app authorization once they
 * are being redirected back from the Google authorization with all required access.
 *
 * Please note that this hook is a special business logic exclusive to the onboarding flow
 * and should not be used elsewhere.
 *
 * @param {string} page The page where this hook is being used. It's used for tracking purposes.
 * @return {null|boolean} A state that indicates whether the caller can continue the subsequent process.
 *   - `null` if it's still loading the required data for determining whether to do the redirection.
 *   - `true` if it's ready to continue the subsequent process.
 *   - `false` if it's processing the redirection and the caller should block the subsequent process.
 *
 * @fires gla_enable_product_sync with `{ page: 'setup-mc', context: 'auto-redirection' }`
 */
export default function useAutoWPComAppAuthorization( page ) {
	const { jetpack } = useJetpackAccount();
	const { google, scope } = useGoogleAccount();
	const { googleMCAccount, isWPComAppGranted } = useGoogleMCAccount();
	const { fetchWPComAppAuthorizationUrl } = useAppDispatch();
	const lockRef = useRef( null );
	const [ , forceUpdate ] = useReducer( ( x ) => x + 1, 0 );

	const query = getQuery();
	const isBackFromGoogleAuth = query[ 'google-mc' ] === 'connected';

	// This mechanism is only triggered when the user is being redirected back
	// from Google authorization.
	if ( ! isBackFromGoogleAuth ) {
		return true;
	}

	// These data are fetched sequentially and conditionally in their hooks
	// layer by layer. Each layer doesn't actually start fetching data from API
	// until the data it depends on is available. Therefore, it needs to check
	// the loading states layer by layer according to the dependencies.
	const isLoadingJetpack = ! jetpack;
	const isLoadingGoogle = jetpack?.active === 'yes' && ! google;
	const isLoadingGMC =
		google?.active === 'yes' && scope.gmcRequired && ! googleMCAccount;

	if ( isLoadingJetpack || isLoadingGoogle || isLoadingGMC ) {
		return null;
	}

	if ( scope.onboardingRequired && ! isWPComAppGranted ) {
		// Make sure it only triggers the redirection one time.
		if ( lockRef.current === null ) {
			lockRef.current = false;

			recordGlaEvent( 'gla_enable_product_sync', {
				page,
				context: 'auto-redirection',
			} );

			fetchWPComAppAuthorizationUrl()
				.then( ( authUrl ) => {
					window.location.href = authUrl;
				} )
				.catch( () => {
					// Silently fall back to the subsequent process if any error occurs.
					lockRef.current = true;
					forceUpdate();
				} );
		}
	} else {
		lockRef.current = true;
	}

	return lockRef.current;
}
