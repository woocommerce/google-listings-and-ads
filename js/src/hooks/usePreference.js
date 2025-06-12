/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';

/**
 * Hook to get a specific preference value.
 *
 * @param {string} key The preference key.
 * @return {*} The preference value.
 */
const usePreference = ( key ) => {
	return useSelect(
		( select ) =>
			select( preferencesStore ).get( PREFERENCES_STORE_NAMESPACE, key ),
		[ key ]
	);
};

export default usePreference;
