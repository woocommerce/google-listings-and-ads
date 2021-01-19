/**
 * External dependencies
 */
// import { apiFetch } from '@wordpress/data-controls';
// import { dispatch } from '@wordpress/data';
// import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TYPES from './action-types';

export function setAudienceSelectedCountries( selected ) {
	return {
		type: TYPES.SET_AUDIENCE_SELECTED_COUNTRIES,
		selected,
	};
}
