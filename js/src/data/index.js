/**
 * External dependencies
 */
import { registerStore, select, useDispatch, dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import { STORE_KEY } from './constants';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';
import { controls } from './controls';
import reducer from './reducer';
import { createErrorResponseCatcher } from './apiFetchMiddlewares';
import { getReconnectAccountUrl } from '~/utils/urls';

// This module is bundled separately per script entry, so more than one entry
// (e.g. the main app and the notifications-system bundle) can import it on
// the same page. Guard against re-running this setup — and re-registering
// the store, which `@wordpress/data` only warns about rather than prevents —
// so only the first bundle to load performs it.
if ( ! select( STORE_KEY ) ) {
	registerStore( STORE_KEY, {
		actions,
		selectors,
		resolvers,
		controls,
		reducer,
	} );

	dispatch( STORE_KEY ).hydratePrefetchedData( glaData.initialWpData );

	apiFetch.use(
		createErrorResponseCatcher( ( response ) => {
			if ( glaData.mcSetupComplete && response.status === 401 ) {
				return ( response.json || response.text )
					.call( response )
					.then( ( errorInfo ) => {
						if ( typeof errorInfo === 'string' ) {
							return { message: errorInfo };
						}
						return errorInfo;
					} )
					.then( ( errorInfo ) => {
						const url = getReconnectAccountUrl( errorInfo.code );

						if ( url ) {
							getHistory().replace( url );
						}

						return errorInfo;
					} )
					.then( ( errorInfo ) => {
						// Inject the status code to let the subsequent handlers can identify the 401 response error.
						return Promise.reject( {
							...errorInfo,
							statusCode: response.status,
						} );
					} );
			}

			// Throws error response to subsequent middlewares
			throw response;
		} )
	);
}

export { STORE_KEY };

export const useAppDispatch = () => {
	return useDispatch( STORE_KEY );
};
