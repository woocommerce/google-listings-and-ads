/**
 * External dependencies
 */
import { controls } from '@wordpress/data-controls';
import { registerStore, useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import { STORE_KEY } from './constants';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';
import reducer from './reducer';
import { createErrorResponseCatcher } from './api-fetch-middlewares';
import { getReconnectAccountUrl } from '.~/utils/urls';

registerStore( STORE_KEY, {
	actions,
	selectors,
	resolvers,
	controls,
	reducer,
} );

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

export { STORE_KEY };

export const useAppDispatch = () => {
	return useDispatch( STORE_KEY );
};
