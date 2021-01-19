/**
 * External dependencies
 */
import { cloneDeep } from 'lodash';

/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	mc: {
		audience: {
			location: {
				option: 'selected',
				selected: [ 'AU' ],
			},
		},
	},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.SET_AUDIENCE_SELECTED_COUNTRIES: {
			const newState = cloneDeep( state );
			newState.mc.audience.location.selected = action.selected;
			return newState;
		}

		default:
			return state;
	}
};

export default reducer;
