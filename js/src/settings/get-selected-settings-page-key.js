/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { pagePaths, subpaths } from '.~/utils/urls';

/**
 * Internal dependencies
 */

/**
 * Get the selected tab key for Settings Navigation
 *
 * @return {string} The selected tab key
 */
const getSelectedSettingsPageKey = () => {
	const query = getQuery();
	return query?.subpath === subpaths.attributeMapping
		? subpaths.attributeMapping
		: pagePaths.settings;
};

export default getSelectedSettingsPageKey;
