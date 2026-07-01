/**
 * Internal dependencies
 */
import TYPES from './action-types';

export const registerNotifications = ( notifications ) => ( {
	type: TYPES.REGISTER_NOTIFICATIONS,
	notifications,
} );

export const setNotifications = ( notifications ) => ( {
	type: TYPES.SET_NOTIFICATIONS,
	notifications,
} );

export const dismissNotification = ( id ) => ( {
	type: TYPES.DISMISS_NOTIFICATION,
	id,
} );
