/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';
import useMCCountries from '.~/hooks/useMCCountries';

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * Gets the final country codes from the Target Audience page.
 * This will call the `getTargetAudience` selector and `useMCCountries` hook.
 * Returns `{ loading, data, targetAudience, getFinalCountries }`.
 *
 * `loading` is true when both `getTargetAudience` and `useMCCountries` are still resolving.
 *
 * `data` is:
 * - `undefined` when loading is in progress;
 * - an array of all supported country codes when users chose `all` in target audience page;
 * - an array of selected country codes when users chose `selected` in target audience page.
 *
 * `targetAudience` is currentyl stored target audience, see `getTargetAudience` selector.
 *
 * `getFinalCountries` is a function to resolve given `targetAudience` to final list of countries.
 *
 */
const useTargetAudienceFinalCountryCodes = () => {
	const {
		data: supportedCountries,
		isResolving: countriesLoading,
	} = useMCCountries();

	function mapSelect( select ) {
		const { getTargetAudience, isResolving } = select( STORE_KEY );
		const storedTargetAudience = getTargetAudience();
		const targetAudienceLoading = isResolving( 'getTargetAudience' );

		/**
		 * Flag to indicate that the data is loading.
		 *
		 * @type {boolean}
		 */
		const loading = targetAudienceLoading || countriesLoading;
		const allCountries =
			supportedCountries && Object.keys( supportedCountries );

		/**
		 * Resolves countries from given targetAudience.
		 * If `targetAudience.location` is set to `'all'` returns the country codes of all currentyl supported countries.
		 *
		 * @param {Object} targetAudience Target audience object to resolve.
		 * @param {string} targetAudience.location
		 * @param {string} targetAudience.countries
		 *
		 * @return {Array<CountryCode>} `targetAudience.countries` or all supported country codes.
		 */
		function getFinalCountries( targetAudience ) {
			return targetAudience?.location === 'all'
				? allCountries
				: targetAudience?.countries;
		}

		/**
		 * Final list of country codes.
		 */
		const data = getFinalCountries( storedTargetAudience );

		return {
			loading,
			data,
			targetAudience: storedTargetAudience,
			getFinalCountries,
		};
	}

	return useSelect( mapSelect, [ supportedCountries, countriesLoading ] );
};

export default useTargetAudienceFinalCountryCodes;
