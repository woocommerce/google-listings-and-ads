/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useStoreCountry from '~/hooks/useStoreCountry';

/**
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

/**
 * Hook to resolve the country codes for querying the ads budget recommendation
 * or metrics. If the store country is included in the given country codes, it
 * will be moved to the first position in the array as the primary country.
 *
 * @param {Array<CountryCode>} [countryCodes] An array of country codes.
 * @return {Array<CountryCode>} The resolved country codes for querying the ads budget.
 */
export default function useCountryCodesForBudgetQuery( countryCodes ) {
	const { code: storeCountry } = useStoreCountry();

	return useMemo( () => {
		if ( ! storeCountry || ! countryCodes ) {
			return [];
		}

		const idx = countryCodes.indexOf( storeCountry );

		if ( idx > 0 ) {
			const codes = countryCodes.slice();
			return codes.splice( idx, 1 ).concat( codes );
		}

		return countryCodes;
	}, [ storeCountry, countryCodes ] );
}
