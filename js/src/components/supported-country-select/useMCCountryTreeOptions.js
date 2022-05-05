/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 * @typedef {import('.~/components/tree-select-control').Option} Option
 */

const EMPTY_ARRAY = [];

/**
 * A hook converts the given country codes to tree-based option structure,
 * which are grouped by continents, for using with <TreeSelectControl> component.
 *
 * Valid countries are those supported by Google Merchant Center.
 *
 * @param {Array<CountryCode>} [countryCodes] Country codes for converting to tree-based option structure. It will use all MC supported countries if not specified.
 *
 * @return {Array<Option>} The converted tree-based options.
 */
export default function useMCCountryTreeOptions( countryCodes ) {
	const {
		data: { countries, continents },
		hasFinishedResolution,
	} = useAppSelectDispatch( 'getMCCountriesAndContinents' );

	return useMemo( () => {
		if ( ! hasFinishedResolution ) {
			return EMPTY_ARRAY;
		}

		const outputCountryCodes = countryCodes || Object.keys( countries );

		function selectCountryOptions( mcCountryCodes ) {
			return mcCountryCodes.reduce( ( acc, code ) => {
				if ( outputCountryCodes.includes( code ) ) {
					acc.push( {
						value: code,
						label: countries[ code ].name,
					} );
				}
				return acc;
			}, [] );
		}

		return Object.entries( continents ).reduce(
			( acc, [ code, continent ] ) => {
				const children = selectCountryOptions( continent.countries );

				if ( children.length ) {
					acc.push( {
						value: code,
						label: continent.name,
						children,
					} );
				}
				return acc;
			},
			[]
		);
	}, [ countryCodes, countries, continents, hasFinishedResolution ] );
}
