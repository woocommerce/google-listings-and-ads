/**
 * Internal dependencies
 */
import TYPES from './action-types';

export default function reducer( state = [], action ) {
	switch ( action.type ) {
		case TYPES.REGISTER_NOTIFICATIONS:
			return [ ...state, ...action.notifications ];

		case TYPES.DISMISS_NOTIFICATION:
			return state.filter(
				( notification ) => notification.id !== action.id
			);

		default:
			return state;
	}
}
