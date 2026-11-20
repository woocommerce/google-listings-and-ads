/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useCallback, useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data';
import useMCCountries from '~/hooks/useMCCountries';

/**
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

/**
 * Gets the final country codes from the Target Audience page.
 * This will call the `getTargetAudience` selector and `useMCCountries` hook.
 * Returns `{ loaded, data, targetAudience, getFinalCountries }`.
 *
 * `loaded` is true when both `getTargetAudience` and `useMCCountries` have finished resolving.
 *
 * `data` is:
 * - `undefined` when loading is in progress;
 * - an array of all supported country codes when users chose `all` in target audience page;
 * - an array of selected country codes when users chose `selected` in target audience page.
 *
 * `targetAudience` is currently stored target audience, see `getTargetAudience` selector.
 *
 * `getFinalCountries` is a function to resolve given `targetAudience` to final list of countries.
 *
 */
const useTargetAudienceFinalCountryCodes = () => {
	const { data: supportedCountries, hasFinishedResolution: countriesLoaded } =
		useMCCountries();

	const { targetAudience, targetAudienceLoaded } = useSelect( ( select ) => {
		const { getTargetAudience, hasFinishedResolution } =
			select( STORE_KEY );

		return {
			targetAudience: getTargetAudience(),
			targetAudienceLoaded: hasFinishedResolution( 'getTargetAudience' ),
		};
	}, [] );

	/**
	 * Resolves countries from given targetAudience.
	 * If `targetAudience.location` is set to `'all'` returns the country codes of all currently supported countries.
	 *
	 * @param {Object} audience Target audience object to resolve.
	 * @param {string} audience.location
	 * @param {string} audience.countries
	 *
	 * @return {Array<CountryCode>} `audience.countries` or all supported country codes.
	 */
	const getFinalCountries = useCallback(
		( audience ) => {
			return audience?.location === 'all'
				? supportedCountries && Object.keys( supportedCountries )
				: audience?.countries;
		},
		[ supportedCountries ]
	);

	// The values are derived outside `useSelect`, as they'd otherwise be new
	// instances on every call and trigger needless re-renders.
	return useMemo(
		() => ( {
			/**
			 * Flag to indicate that the data has been loaded.
			 *
			 * @type {boolean}
			 */
			loaded: countriesLoaded && targetAudienceLoaded,
			/**
			 * Final list of country codes.
			 */
			data: getFinalCountries( targetAudience ),
			targetAudience,
			getFinalCountries,
		} ),
		[
			countriesLoaded,
			targetAudienceLoaded,
			targetAudience,
			getFinalCountries,
		]
	);
};

export default useTargetAudienceFinalCountryCodes;
