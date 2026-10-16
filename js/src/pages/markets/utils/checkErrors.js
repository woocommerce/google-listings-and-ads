/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SHIPPING_RATE_METHOD, glaData } from '~/constants';
import { PRIMARY_MARKET_ID } from '../constants';

/**
 * @typedef {import('~/data/selectors').MCLanguage} MCLanguage
 */

/**
 * Validates the market form values.
 *
 * @param {Object} values Market form values.
 * @param {Array<MCLanguage>|null} [languages] Languages from the `mc/markets/languages-currencies`
 *   endpoint. Merchant Center support comes from each entry's `supported` flag, computed by the
 *   back end, so the form accepts exactly the languages products can be synced with.
 * @return {Object} Errors keyed by field name.
 */
const checkErrors = ( values, languages = [] ) => {
	const { isMultiLingualStore } = glaData;
	const isPrimary = values.id === PRIMARY_MARKET_ID;
	const { shipping_rate } = values;
	const errors = {};

	// Audience validation: skip for non-primary, non-multilingual, MANUAL markets
	// (the audience field is not shown in the form for that combination).
	const validateAudience =
		isPrimary ||
		shipping_rate !== SHIPPING_RATE_METHOD.MANUAL ||
		isMultiLingualStore;

	if ( validateAudience ) {
		if ( isPrimary ) {
			if ( ( values.countries ?? [] ).length === 0 ) {
				errors.countries = __(
					'Please select at least one country.',
					'google-listings-and-ads'
				);
			}
		} else if ( ! values.country ) {
			errors.country = __(
				'Please select a market.',
				'google-listings-and-ads'
			);
		}
	}

	// Locale validation: language + currency required for multilingual non-flat markets.
	if ( isMultiLingualStore && shipping_rate !== SHIPPING_RATE_METHOD.FLAT ) {
		if ( ( values.language ?? [] ).length === 0 ) {
			errors.language = __(
				'Please select at least one language.',
				'google-listings-and-ads'
			);
		}
		if ( ( values.currency ?? [] ).length === 0 ) {
			errors.currency = __(
				'Please select at least one currency.',
				'google-listings-and-ads'
			);
		}
	}

	// MC language support check applies to all multilingual markets (including flat-rate).
	// For flat-rate markets, language is [] (field not shown), so unsupportedLanguages
	// is always empty and this block is a no-op — it only fires for non-flat markets
	// that already have language values submitted.
	if (
		isMultiLingualStore &&
		! errors.language &&
		( values.language ?? [] ).length > 0
	) {
		// A code the back end didn't report (e.g. no longer active in the multilingual
		// plugin) is treated as unsupported, since products can't be synced with it either.
		const supportedLanguages = new Set(
			( languages ?? [] )
				.filter( ( language ) => language.supported )
				.map( ( language ) => language.code )
		);
		const unsupportedLanguages = ( values.language ?? [] ).filter(
			( language ) => ! supportedLanguages.has( language )
		);

		if ( unsupportedLanguages.length > 0 ) {
			// Name each language as the multilingual plugin labels it (e.g. "Bulgarian"),
			// falling back to the code when the back end didn't report it.
			const labels = new Map(
				( languages ?? [] ).map( ( language ) => [
					language.code,
					language.label,
				] )
			);

			errors.language = sprintf(
				// translators: %s: comma-separated list of unsupported language names.
				__(
					'The following languages are not supported by Google Merchant Center: %s',
					'google-listings-and-ads'
				),
				unsupportedLanguages
					.map( ( code ) => labels.get( code ) || code )
					.join( ', ' )
			);
		}
	}

	if ( shipping_rate === SHIPPING_RATE_METHOD.FLAT ) {
		if (
			values.flat_shipping_rate === null ||
			values.flat_shipping_rate === undefined ||
			values.flat_shipping_rate < 0
		) {
			errors.flat_shipping_rate = __(
				'Please enter a valid shipping rate.',
				'google-listings-and-ads'
			);
		}

		if (
			values.offer_free_shipping === true &&
			! values.free_shipping_threshold
		) {
			errors.free_shipping_threshold = __(
				'Please enter minimum order for free shipping.',
				'google-listings-and-ads'
			);
		}
	}

	if (
		shipping_rate === SHIPPING_RATE_METHOD.FLAT ||
		shipping_rate === SHIPPING_RATE_METHOD.AUTOMATIC
	) {
		if (
			values.flat_shipping_min_time === null ||
			values.flat_shipping_min_time === undefined
		) {
			errors.flat_shipping_times = __(
				'Please specify an estimated minimum shipping time.',
				'google-listings-and-ads'
			);
		} else if (
			values.flat_shipping_max_time === null ||
			values.flat_shipping_max_time === undefined
		) {
			errors.flat_shipping_times = __(
				'Please specify an estimated maximum shipping time.',
				'google-listings-and-ads'
			);
		} else if (
			values.flat_shipping_min_time < 0 ||
			values.flat_shipping_max_time < 0
		) {
			errors.flat_shipping_times = __(
				'The shipping time cannot be less than 0.',
				'google-listings-and-ads'
			);
		} else if (
			values.flat_shipping_min_time > values.flat_shipping_max_time
		) {
			errors.flat_shipping_times = __(
				'The minimum shipping time must not be more than the maximum shipping time.',
				'google-listings-and-ads'
			);
		}
	}

	return errors;
};

export default checkErrors;
