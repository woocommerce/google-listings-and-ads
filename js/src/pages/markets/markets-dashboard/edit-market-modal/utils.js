export function buildShippingRatesPayload(
	values,
	audienceCountryCodes,
	existingRates
) {
	const rates = existingRates ?? [];
	const existingByCountry = new Map( rates.map( ( r ) => [ r.country, r ] ) );
	const currency = values.shipping_currency ?? rates[ 0 ]?.currency;

	return audienceCountryCodes.map( ( country ) => {
		const existing = existingByCountry.get( country );
		let threshold;
		if ( values.offer_free_shipping ) {
			threshold =
				values.free_shipping_threshold > 0
					? values.free_shipping_threshold
					: existing?.options?.free_shipping_threshold;
		} else {
			threshold = undefined;
		}

		return {
			id: existing?.id,
			country,
			currency,
			rate: values.flat_shipping_rate,
			options: { free_shipping_threshold: threshold },
		};
	} );
}

export function buildShippingTimesPayload( values, audienceCountryCodes ) {
	return audienceCountryCodes.map( ( countryCode ) => ( {
		countryCode,
		time: values.flat_shipping_min_time,
		maxTime: values.flat_shipping_max_time,
	} ) );
}
