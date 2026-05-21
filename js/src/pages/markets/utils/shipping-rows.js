/**
 * Helpers for synchronising the form's `shipping_country_rates` and
 * `shipping_country_times` arrays with single-input fields like
 * `flat_shipping_rate` and `flat_shipping_min_time`.
 *
 * "Ensure" helpers materialise a row for any target country that doesn't
 * already have one — without them, typing a value for a brand-new
 * secondary market (or one whose country has no stored rate yet) is a no-op
 * because `Array#map` never matches and the form's value stays unchanged.
 *
 * "Update" helpers patch the rows whose country is in the target list and
 * leave the rest untouched.
 */

/**
 * Returns the list of country codes whose shipping rate/time rows should be
 * touched by the next form change.
 *
 * @param {boolean} isPrimaryMarket
 * @param {Object}  values Current form values.
 * @return {string[]} ISO 3166-1 alpha-2 country codes.
 */
export function getTargetCountries( isPrimaryMarket, values ) {
	if ( isPrimaryMarket ) {
		return values.countries || [];
	}
	return values.country ? [ values.country ] : [];
}

/**
 * Appends rate rows for any target country missing from `rates`.
 *
 * @param {Array}    rates           Current shipping_country_rates value.
 * @param {string[]} targetCountries Country codes that need a row.
 * @param {string}   currency        Currency to seed onto newly-created rows.
 * @return {Array} A new array with rows for every target country.
 */
export function ensureRateRows( rates, targetCountries, currency ) {
	const existing = new Set( rates.map( ( rate ) => rate.country ) );
	const newRows = targetCountries
		.filter( ( code ) => ! existing.has( code ) )
		.map( ( code ) => ( {
			country: code,
			currency,
			rate: 0,
			options: {},
		} ) );
	return newRows.length ? [ ...rates, ...newRows ] : rates;
}

/**
 * Appends time rows for any target country missing from `times`.
 *
 * @param {Array}    times           Current shipping_country_times value.
 * @param {string[]} targetCountries Country codes that need a row.
 * @return {Array} A new array with rows for every target country.
 */
export function ensureTimeRows( times, targetCountries ) {
	const existing = new Set( times.map( ( entry ) => entry.countryCode ) );
	const newRows = targetCountries
		.filter( ( code ) => ! existing.has( code ) )
		.map( ( code ) => ( {
			countryCode: code,
			time: 0,
			maxTime: 0,
		} ) );
	return newRows.length ? [ ...times, ...newRows ] : times;
}

/**
 * Patches top-level fields on rate rows whose country is in `targetCountries`.
 */
export function updateRates( rates, targetCountries, patch ) {
	const targets = new Set( targetCountries );
	return rates.map( ( rate ) =>
		targets.has( rate.country ) ? { ...rate, ...patch } : rate
	);
}

/**
 * Patches the `options` object on matching rate rows (merges, does not replace).
 */
export function updateRateOptions( rates, targetCountries, optionsPatch ) {
	const targets = new Set( targetCountries );
	return rates.map( ( rate ) =>
		targets.has( rate.country )
			? { ...rate, options: { ...rate.options, ...optionsPatch } }
			: rate
	);
}

/**
 * Patches top-level fields on time rows whose country is in `targetCountries`.
 */
export function updateTimes( times, targetCountries, patch ) {
	const targets = new Set( targetCountries );
	return times.map( ( entry ) =>
		targets.has( entry.countryCode ) ? { ...entry, ...patch } : entry
	);
}
