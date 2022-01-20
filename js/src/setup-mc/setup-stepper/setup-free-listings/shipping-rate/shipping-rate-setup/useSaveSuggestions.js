/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import groupShippingRatesByPriceCurrency from '.~/utils/groupShippingRatesByPriceCurrency';
import { useAppDispatch } from '.~/data';

/**
 * Convert shipping rates suggestions to aggregated shipping rates
 * that are ready to be saved.
 *
 * @param {Array<import('.~/data/actions').ShippingRate>} suggestions Shipping rate suggestions.
 * @return {Array<import('.~/data/actions').AggregatedShippingRate>} Aggregated shipping rates.
 */
const convertSuggestionsToAggregatedShippingRates = ( suggestions ) => {
	const countriesPriceArray = groupShippingRatesByPriceCurrency(
		suggestions
	);
	const values = countriesPriceArray.map( ( el ) => ( {
		countryCodes: el.countries,
		currency: el.currency,
		rate: el.price,
	} ) );

	return values;
};

/**
 * A hook that returns a `saveSuggestions` callback.
 *
 * If there is an error during saving suggestions,
 * it will display an error notice in the UI.
 *
 * @return {Function} `saveSuggestions` function to save suggestions as shipping rates.
 */
const useSaveSuggestions = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { upsertShippingRates } = useAppDispatch();

	const saveSuggestions = useCallback(
		async ( suggestions ) => {
			try {
				const shippingRates = convertSuggestionsToAggregatedShippingRates(
					suggestions
				);
				const promises = shippingRates.map( ( el ) => {
					return upsertShippingRates( el );
				} );
				await Promise.all( promises );
			} catch ( error ) {
				createNotice(
					'error',
					__(
						`Unable to use your WooCommerce shipping settings as shipping rates in Google. You may have to enter shipping rates manually.`,
						'google-listings-and-ads'
					)
				);
			}
		},
		[ createNotice, upsertShippingRates ]
	);

	return saveSuggestions;
};

export default useSaveSuggestions;
