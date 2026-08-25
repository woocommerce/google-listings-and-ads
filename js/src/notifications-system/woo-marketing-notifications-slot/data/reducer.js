/**
 * Internal dependencies
 */
import TYPES from './action-types';

export default function reducer( state = [], action ) {
	switch ( action.type ) {
		// Replaces the full set rather than appending, since the caller always
		// registers the complete, current list of notifications it should be
		// showing (re-fetched from the server) rather than a delta.
		case TYPES.REGISTER_NOTIFICATIONS:
			return action.notifications;

		case TYPES.DISMISS_NOTIFICATION:
			return state.filter(
				( notification ) => notification.id !== action.id
			);

		default:
			return state;
	}
}
