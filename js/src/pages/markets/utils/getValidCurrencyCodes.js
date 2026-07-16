/**
 * Determines which currencies are valid for the given selected language(s).
 * A currency is valid if it's enabled for at least one of the selected
 * languages (union across multiple selected languages). If no language is
 * selected, every currency is considered valid.
 *
 * @param {Array<{code: string, languages?: Array<string>}>} currencies Available currencies, each with the language codes it's enabled for.
 * @param {Array<string>} selectedLanguages Currently selected language codes.
 * @return {Set<string>} Set of valid currency codes.
 */
export default function getValidCurrencyCodes( currencies, selectedLanguages ) {
	const allCurrencies = currencies ?? [];

	if ( ! selectedLanguages?.length ) {
		return new Set( allCurrencies.map( ( currency ) => currency.code ) );
	}

	return new Set(
		allCurrencies
			.filter( ( currency ) =>
				currency.languages?.some( ( code ) =>
					selectedLanguages.includes( code )
				)
			)
			.map( ( currency ) => currency.code )
	);
}
