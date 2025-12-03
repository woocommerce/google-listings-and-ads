/**
 * Internal dependencies
 */
import { glaData } from '~/constants';

export const enabledFeatures = new Set( glaData?.enabledFeatures || [] );

/**
 * Returns true if a feature is enabled; false otherwise.
 *
 * @param {string} feature            The name of the feature to check.
 * @param {Set}    [_enabledFeatures] Optional. The set of enabled features. Uses `enabledFeatures` set by the server in a global JS variable, by default.
 * @return {boolean} `true` if a feature is enabled; `false` otherwise.
 */
export function isFeatureEnabled(
	feature,
	_enabledFeatures = enabledFeatures
) {
	if ( ! ( _enabledFeatures instanceof Set ) ) {
		return false;
	}
	return _enabledFeatures.has( feature );
}
