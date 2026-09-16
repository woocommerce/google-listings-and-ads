/**
 * External dependencies
 */
import { createReduxStore, dispatch, register, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import * as actions from './actions';
import * as selectors from './selectors';
import reducer from './reducer';

/**
 * Registers the shared Redux store for the notifications system.
 *
 * Idempotent — safe to call multiple times or from multiple plugins.
 * The first caller wins; subsequent calls are no-ops.
 */
export function registerStore() {
	if ( select( STORE_KEY ) ) {
		return;
	}

	const store = createReduxStore( STORE_KEY, {
		reducer,
		actions,
		selectors,
	} );

	register( store );
}

export const registerNotifications = ( notifications ) => {
	dispatch( STORE_KEY ).registerNotifications( notifications );
};
