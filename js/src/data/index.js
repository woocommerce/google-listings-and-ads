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
import { STORE_KEY } from './constants';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';
import reducer from './reducer';
import { createErrorResponseCatcher } from './api-fetch-middlewares';
import { getReconnectAccountsUrl } from '.~/utils/urls';

registerStore( STORE_KEY, {
	actions,
	selectors,
	resolvers,
	controls,
	reducer,
} );

apiFetch.use(
	createErrorResponseCatcher( ( response ) => {
		if ( response.status === 401 ) {
			getHistory().replace( getReconnectAccountsUrl() );
		}

		// Throws error response to subsequent middlewares
		throw response;
	} )
);

export { STORE_KEY };

export const useAppDispatch = () => {
	return useDispatch( STORE_KEY );
};
