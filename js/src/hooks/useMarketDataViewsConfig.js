/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import { PRIMARY_MARKET_ID } from '~/pages/markets/constants';
import useMarkets from '~/hooks/useMarkets';
import usePrimaryMarketDetails from '~/hooks/usePrimaryMarketDetails';
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';
import useSettings from '~/hooks/useSettings';
import useShippingRates from '~/hooks/useShippingRates';
import useShippingTimes from '~/hooks/useShippingTimes';

/**
 * @typedef {Object} TimeRow
 * @property {number} time Minimum shipping days.
 * @property {number} maxTime Maximum shipping days.
 */

/**
 * @typedef {Object} RateRow
 * @property {string} currency ISO currency code.
 * @property {number} rate Shipping rate amount.
 * @property {Object} [options] Optional rate modifiers.
 * @property {number} [options.free_shipping_threshold] Free shipping threshold amount.
 */

/**
 * @typedef {Object} Market
 * @property {string} id Market identifier.
 * @property {string} country ISO country code for the market's primary country.
 * @property {string[]} countries All ISO country codes belonging to the market.
 * @property {string} label Display name.
 * @property {string} [language] BCP-47 language tag (multilingual stores only).
 * @property {string} [currency] ISO currency code (multilingual stores only).
 */

/**
 * @typedef {Object} DataViewsConfig
 * @property {Array} fields DataViews column definitions.
 * @property {Array} data Pre-formatted row objects.
 */

/**
 * @typedef {Object} MarketDataViewsResult
 * @property {Array} fields DataViews column definitions.
 * @property {Array} data Pre-formatted row objects.
 * @property {boolean} hasFinishedResolution Whether all data has loaded.
 */

const isPrimaryMarket = ( market ) => market.id === PRIMARY_MARKET_ID;

/**
 * Centralized configuration for the MarketDataViews component.
 * Defines the fields and data shape for each shipping scenario, and handles formatting of shipping rates and times.
 * The main hook at the bottom picks the active scenario based on settings and data, and returns the appropriate config.
 * Each scenario builder returns an object with `fields` (DataViews column definitions) and `data` (pre-formatted rows).
 *
 * Scenarios:
 * - Manual non-multilingual: Market, Country (count), Shipping (static "Managed in Google"). Only primary market shown.
 * - Manual multilingual: Market (label + country count for primary, country name for secondaries), Language, Currency. All markets shown.
 * - Flat (multilingual or not): Market, Shipping Rate, Shipping Time, Free shipping. All markets shown. Both store types render identically per Figma; the multilingual variant of this scenario is GOOWOO-602.
 * - Automatic multilingual: Market, Language, Currency, Shipping time. All markets shown.
 * - Automatic non-multilingual: Market (label + country count), Shipping time. Only primary market shown.
 * - Default/fall-through: Market + Shipping time for all markets, with primary market showing country count in label.
 */
const ALL_FIELDS = {
	market: {
		id: 'market',
		label: __( 'Market', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
		render: ( { item } ) => (
			<span className="gla-markets-table__market-cell">
				{ item.label }
			</span>
		),
	},
	country: {
		id: 'country',
		label: __( 'Country', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
	},
	language: {
		id: 'language',
		label: __( 'Language', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
	},
	currency: {
		id: 'currency',
		label: __( 'Currency', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
	},
	shipping: {
		id: 'shipping',
		label: __( 'Shipping', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
	},
	shippingRate: {
		id: 'shippingRate',
		label: __( 'Shipping rate', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
	},
	shippingTime: {
		id: 'shippingTime',
		label: __( 'Shipping times', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
	},
	freeShipping: {
		id: 'freeShipping',
		label: __( 'Free shipping', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
	},
};

/**
 * Formats a shipping rate row into a currency string, or returns '-' if no rate.
 *
 * @param {RateRow|undefined} rateRow
 * @return {string} Formatted currency string or '-'.
 */
const formatShippingRate = ( rateRow ) => {
	if ( ! rateRow ) {
		return '-';
	}

	return new Intl.NumberFormat( undefined, {
		style: 'currency',
		currency: rateRow.currency,
	} ).format( rateRow.rate );
};

/**
 * Formats a shipping time row into a human-readable string, or returns '-' if no time.
 *
 * @param {TimeRow|undefined} timeRow
 * @return {string} Formatted days string or '-'.
 */
const formatShippingTime = ( timeRow ) => {
	if ( ! timeRow ) {
		return '-';
	}
	const { time, maxTime } = timeRow;

	if ( time === 0 && maxTime === 0 ) {
		return __( 'Same day', 'google-listings-and-ads' );
	}

	if ( time === maxTime ) {
		return sprintf(
			// translators: %d: number of shipping days.
			__( '%d days', 'google-listings-and-ads' ),
			time
		);
	}

	return sprintf(
		// translators: 1: minimum shipping days, 2: maximum shipping days.
		__( '%1$d - %2$d days', 'google-listings-and-ads' ),
		time,
		maxTime
	);
};

/**
 * Formats a shipping rate row into a free shipping string:
 * - If rate is 0, returns 'Free'.
 * - If there's a free shipping threshold, returns 'Free over $X'.
 * - Otherwise, returns '-'.
 *
 * @param {RateRow|undefined} rateRow
 * @return {string} 'Free', 'Free over $X', or '-'.
 */
const formatFreeShipping = ( rateRow ) => {
	if ( ! rateRow ) {
		return '-';
	}

	if ( rateRow.rate === 0 ) {
		return __( 'Free', 'google-listings-and-ads' );
	}

	const threshold = rateRow.options?.free_shipping_threshold;
	if ( threshold !== null && threshold !== undefined ) {
		const formatted = new Intl.NumberFormat( undefined, {
			style: 'currency',
			currency: rateRow.currency,
		} ).format( threshold );

		return sprintf(
			// translators: %s: currency-formatted free shipping threshold, e.g. "$50.00".
			__( 'Free over %s', 'google-listings-and-ads' ),
			formatted
		);
	}

	return '-';
};

/**
 * Manual shipping scenario. The column set differs based on whether the store
 * has multilingual support:
 *
 * - Multilingual: Market (label + country count for primary, country name for
 *   secondaries), Language, Currency — all markets as rows.
 * - Non-multilingual: Market, Country (count), Shipping (static "Managed in
 *   Google") — primary market only.
 *
 * @param {Object}     options
 * @param {Market}     options.primaryMarket       Primary market data from usePrimaryMarketDetails.
 * @param {Market[]}   options.markets             All markets from useMarkets.
 * @param {boolean}    options.isMultiLingualStore Whether the store has a multilingual plugin.
 * @return {DataViewsConfig} DataViews fields and pre-formatted rows.
 */
const buildManualConfig = ( {
	primaryMarket,
	markets,
	isMultiLingualStore,
} ) => {
	if ( isMultiLingualStore ) {
		const fields = [
			ALL_FIELDS.market,
			ALL_FIELDS.language,
			ALL_FIELDS.currency,
		];

		const data = markets.map( ( market ) => {
			if ( ! isPrimaryMarket( market ) ) {
				return market;
			}

			const countryCount = market.countries?.length ?? 0;
			return {
				...market,
				label: sprintf(
					// translators: 1: market label, 2: number of countries.
					_n(
						'%1$s (%2$d country)',
						'%1$s (%2$d countries)',
						countryCount,
						'google-listings-and-ads'
					),
					market.label,
					countryCount
				),
			};
		} );

		return { fields, data };
	}

	const countryCount = primaryMarket?.countries?.length ?? 0;

	const fields = [
		ALL_FIELDS.market,
		ALL_FIELDS.country,
		ALL_FIELDS.shipping,
	];

	const data = primaryMarket
		? [
				{
					...primaryMarket,
					country: sprintf(
						// translators: %d: number of countries.
						_n(
							'%d country',
							'%d countries',
							countryCount,
							'google-listings-and-ads'
						),
						countryCount
					),
					shipping: __(
						'Managed in Google',
						'google-listings-and-ads'
					),
				},
		  ]
		: [];

	return { fields, data };
};

/**
 * Flat shipping scenario: Market, Shipping Rate, Shipping Time, Free shipping.
 * All markets (primary and additional) appear as rows.
 *
 * Covers both non-multilingual and multilingual stores — Figma renders both
 * frames identically, so the builder is shared rather than branched on
 * `isMultiLingualStore`. The multilingual variant is tracked as GOOWOO-602.
 *
 * @param {Object}                  options
 * @param {Market[]}               options.markets        All markets from useMarkets.
 * @param {Object.<string,RateRow>} options.ratesByCountry Country-keyed map of shipping rate rows.
 * @param {Object.<string,TimeRow>} options.timesByCountry Country-keyed map of shipping time rows.
 * @return {DataViewsConfig} DataViews fields and pre-formatted rows.
 */
const buildFlatConfig = ( { markets, ratesByCountry, timesByCountry } ) => {
	const fields = [
		ALL_FIELDS.market,
		ALL_FIELDS.shippingRate,
		ALL_FIELDS.shippingTime,
		ALL_FIELDS.freeShipping,
	];

	const data = markets.map( ( market ) => {
		let country = market.country;
		if ( isPrimaryMarket( market ) && market.countries.length > 0 ) {
			// For the primary market, use the first country in the list to look up rates and times,
			// since theoretically there should not be the country property for that market.
			country = market.countries[ 0 ];
		}

		const rateRow = ratesByCountry[ country ];
		const timeRow = timesByCountry[ country ];
		return {
			...market,
			shippingRate: formatShippingRate( rateRow ),
			shippingTime: formatShippingTime( timeRow ),
			freeShipping: formatFreeShipping( rateRow ),
		};
	} );

	return { fields, data };
};

/**
 * Automatic shipping scenario. The column set differs based on whether the store
 * has multilingual support:
 *
 * - Multilingual: Market, Language, Currency, Shipping time — all markets as rows.
 * - Non-multilingual: Market (label + country count), Shipping time — primary market only.
 *
 * @param {Object}                  options
 * @param {Market[]}               options.markets             All markets from useMarkets.
 * @param {Market}                 options.primaryMarket       Primary market data from usePrimaryMarketDetails.
 * @param {boolean}                options.isMultiLingualStore Whether the store has a multilingual plugin.
 * @param {Object.<string,TimeRow>} options.timesByCountry      Country-keyed map of shipping time rows.
 * @return {DataViewsConfig} DataViews fields and pre-formatted rows.
 */
const buildAutomaticConfig = ( {
	markets,
	primaryMarket,
	isMultiLingualStore,
	timesByCountry,
} ) => {
	if ( isMultiLingualStore ) {
		const fields = [
			ALL_FIELDS.market,
			ALL_FIELDS.language,
			ALL_FIELDS.currency,
			ALL_FIELDS.shippingTime,
		];

		const data = markets.map( ( market ) => ( {
			...market,
			shippingTime: formatShippingTime(
				timesByCountry[ market.country ]
			),
		} ) );

		return { fields, data };
	}

	const countryCount = primaryMarket?.countries?.length ?? 0;

	const fields = [ ALL_FIELDS.market, ALL_FIELDS.shippingTime ];

	const data = primaryMarket
		? [
				{
					...primaryMarket,
					label: sprintf(
						// translators: 1: market label, 2: number of countries.
						_n(
							'%1$s (%2$d country)',
							'%1$s (%2$d countries)',
							countryCount,
							'google-listings-and-ads'
						),
						primaryMarket.label,
						countryCount
					),
					shippingTime: formatShippingTime(
						timesByCountry[ primaryMarket.country ]
					),
				},
		  ]
		: [];

	return { fields, data };
};

/**
 * Fall-through default — preserves the legacy Market + Shipping times shape for
 * any scenario that doesn't yet have a dedicated builder. With every documented
 * scenario now branched, this only catches unexpected `shipping_rate` values
 * and serves as a safety net.
 *
 * @param {Object}              options
 * @param {Market[]}           options.markets      All markets from useMarkets.
 * @param {Object.<string,string>} options.countryNames Mapping of country code to country name from useCountryKeyNameMap.
 * @return {DataViewsConfig} DataViews fields and pre-formatted rows.
 */
const buildDefaultConfig = ( { markets, countryNames } ) => {
	const fields = [ ALL_FIELDS.market, ALL_FIELDS.shippingTime ];

	const data = markets.map( ( market ) => {
		const marketCell = isPrimaryMarket( market )
			? sprintf(
					// translators: 1: market label, 2: number of countries.
					_n(
						'%1$s (%2$d country)',
						'%1$s (%2$d countries)',
						market.countries.length,
						'google-listings-and-ads'
					),
					market.label,
					market.countries.length
			  )
			: countryNames[ market.country ];

		return {
			...market,
			label: marketCell,
		};
	} );

	return { fields, data };
};

/**
 * Single source of truth for the MarketDataViews `{ fields, data }` shape.
 *
 * Picks the active scenario from `settings.shipping_rate` and
 * `glaData.isMultiLingualStore`, formats the rows, and returns the DataViews-ready
 * config. `MarketDataViews` consumes this directly with no scenario branching of
 * its own.
 *
 * @return {MarketDataViewsResult} DataViews fields, pre-formatted rows, and resolution state.
 */
const useMarketDataViewsConfig = () => {
	const { data: markets, hasFinishedResolution: hasResolvedMarkets } =
		useMarkets();
	const { data: primaryMarket } = usePrimaryMarketDetails();
	const countryNames = useCountryKeyNameMap();
	const { settings } = useSettings();
	const { data: shippingRatesData, hasFinishedResolution: hasResolvedRates } =
		useShippingRates();
	const { data: shippingTimesData, hasFinishedResolution: hasResolvedTimes } =
		useShippingTimes();

	const isMultiLingualStore = glaData.isMultiLingualStore ?? false;

	const hasFinishedResolution =
		hasResolvedMarkets &&
		hasResolvedRates &&
		hasResolvedTimes &&
		!! settings;

	if ( ! hasFinishedResolution ) {
		return { fields: [], data: [], hasFinishedResolution };
	}

	const shippingRate = settings.shipping_rate;

	if ( shippingRate === SHIPPING_RATE_METHOD.MANUAL ) {
		return {
			...buildManualConfig( {
				primaryMarket,
				markets,
				isMultiLingualStore,
			} ),
			hasFinishedResolution,
		};
	}

	const ratesByCountry = Object.fromEntries(
		( shippingRatesData || [] ).map( ( rate ) => [ rate.country, rate ] )
	);
	const timesByCountry = Object.fromEntries(
		( shippingTimesData || [] ).map( ( time ) => [
			time.countryCode,
			time,
		] )
	);

	if ( shippingRate === SHIPPING_RATE_METHOD.FLAT ) {
		return {
			...buildFlatConfig( { markets, ratesByCountry, timesByCountry } ),
			hasFinishedResolution,
		};
	}

	if ( shippingRate === SHIPPING_RATE_METHOD.AUTOMATIC ) {
		return {
			...buildAutomaticConfig( {
				markets,
				primaryMarket,
				isMultiLingualStore,
				timesByCountry,
			} ),
			hasFinishedResolution,
		};
	}

	return {
		...buildDefaultConfig( { markets, countryNames } ),
		hasFinishedResolution,
	};
};

export default useMarketDataViewsConfig;
