/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';
import useSettings from '~/hooks/useSettings';
import useShippingRates from '~/hooks/useShippingRates';
import useShippingTimes from '~/hooks/useShippingTimes';
import useMarkets from './useMarkets';
import ShippingRateCell from '../components/market-data-views/shipping-rate-cell';
import LanguageCell from '../components/market-data-views/language-cell';
import CurrencyCell from '../components/market-data-views/currency-cell';
import FreeShippingCell from '../components/market-data-views/free-shipping-cell';
import ShippingTimesCell from '../components/market-data-views/shipping-times-cell';
import isPrimaryMarket from '../utils/isPrimaryMarket';

/**
 * @typedef {import('~/data/actions').Market} Market
 */

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

/**
 * Centralized configuration for the MarketDataViews component.
 * Defines the fields and data shape for each shipping scenario, and handles formatting of shipping rates and times.
 * The main hook at the bottom picks the active scenario based on settings and data, and returns the appropriate config.
 * Each scenario builder returns an object with `fields` (DataViews column definitions) and `data` (pre-formatted rows).
 *
 * Scenarios:
 * - Manual non-multilingual: Market, Country (count), Shipping (static "Managed in Google"). Only primary market shown.
 * - Manual multilingual: Market (label + country count for primary, country name for secondaries), Language, Currency. All markets shown.
 * - Flat (multilingual or not): Market (label + country count for primary), Shipping Rate, Shipping Time, Free shipping. All markets shown. Both store types render identically per Figma; the multilingual variant of this scenario is GOOWOO-602.
 * - Automatic multilingual: Market (label + country count for primary), Language, Currency, Shipping time. All markets shown.
 * - Automatic non-multilingual: Market (label + country count), Shipping time. All markets shown.
 * - Default/fall-through: Market + Shipping time for all markets, with primary market showing country count in label.
 */
const ALL_FIELDS = {
	market: {
		id: 'market',
		label: __( 'Market', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
		enableGlobalSearch: true,
		getValue: ( { item } ) => item.marketSearchValue || item.label,
		render: ( { item } ) => {
			return (
				<span className="gla-markets-table__market-cell">
					{ item.label }
				</span>
			);
		},
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
		render: ( { item } ) => {
			return <LanguageCell market={ item } />;
		},
	},
	currency: {
		id: 'currency',
		label: __( 'Currency', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
		render: ( { item } ) => {
			return <CurrencyCell market={ item } />;
		},
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
		render: ( { item } ) => {
			return <ShippingRateCell market={ item } />;
		},
	},
	shippingTime: {
		id: 'shippingTime',
		label: __( 'Shipping times', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
		render: ( { item } ) => {
			return <ShippingTimesCell market={ item } />;
		},
	},
	freeShipping: {
		id: 'freeShipping',
		label: __( 'Free shipping', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
		render: ( { item } ) => {
			return <FreeShippingCell market={ item } />;
		},
	},
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
 * @param {Object} options
 * @param {Market[]} options.markets All markets from useMarkets.
 * @return {DataViewsConfig} DataViews fields and pre-formatted rows.
 */
const buildManualConfig = ( { markets } ) => {
	if ( glaData.isMultiLingualStore ) {
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

	const primaryMarket = markets.find( isPrimaryMarket );
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
 * `glaData.isMultiLingualStore`. The multilingual variant is tracked as GOOWOO-602.
 *
 * @param {Object} options
 * @param {Market[]} options.markets All markets from useMarkets.
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
			// Primary's country is null by API contract — use the first targeted country for rate/time lookups.
			country = market.countries[ 0 ];
		}

		const rateRow = ratesByCountry[ country ];
		const timeRow = timesByCountry[ country ];

		const row = {
			...market,
			shipping_rate_config: rateRow,
			shipping_time_config: timeRow,
		};

		if ( isPrimaryMarket( market ) ) {
			const countryCount = market.countries?.length ?? 0;
			row.label = sprintf(
				// translators: 1: market label, 2: number of countries.
				_n(
					'%1$s (%2$d country)',
					'%1$s (%2$d countries)',
					countryCount,
					'google-listings-and-ads'
				),
				market.label,
				countryCount
			);
		}

		return row;
	} );

	return { fields, data };
};

/**
 * Automatic shipping scenario. The column set differs based on whether the store
 * has multilingual support:
 *
 * - Multilingual: Market (label + country count for primary, country name for secondaries), Language, Currency, Shipping time — all markets as rows.
 * - Non-multilingual: Market (label + country count), Shipping time — primary market only.
 *
 * @param {Object} options
 * @param {Market[]} options.markets All markets from useMarkets.
 * @param {Object.<string,TimeRow>} options.timesByCountry Country-keyed map of shipping time rows.
 * @return {DataViewsConfig} DataViews fields and pre-formatted rows.
 */
const buildAutomaticConfig = ( { markets, timesByCountry } ) => {
	const fields = glaData.isMultiLingualStore
		? [
				ALL_FIELDS.market,
				ALL_FIELDS.language,
				ALL_FIELDS.currency,
				ALL_FIELDS.shippingTime,
		  ]
		: [ ALL_FIELDS.market, ALL_FIELDS.shippingTime ];

	const data = markets.map( ( market ) => {
		let country = market.country;
		if ( isPrimaryMarket( market ) && market.countries?.length > 0 ) {
			// Primary's country is null by API contract — use the first targeted country for time lookups.
			country = market.countries[ 0 ];
		}

		const row = {
			...market,
			shipping_time_config: timesByCountry[ country ],
		};

		if ( isPrimaryMarket( market ) ) {
			const countryCount = market.countries?.length ?? 0;
			row.label = sprintf(
				// translators: 1: market label, 2: number of countries.
				_n(
					'%1$s (%2$d country)',
					'%1$s (%2$d countries)',
					countryCount,
					'google-listings-and-ads'
				),
				market.label,
				countryCount
			);
		}

		return row;
	} );

	return { fields, data };
};

/**
 * Fall-through default — preserves the legacy Market + Shipping times shape for
 * any scenario that doesn't yet have a dedicated builder. With every documented
 * scenario now branched, this only catches unexpected `shipping_rate` values
 * and serves as a safety net.
 *
 * @param {Object} options
 * @param {Market[]} options.markets All markets from useMarkets.
 * @param {Object.<string,string>} options.countryNames Mapping of country code to country name from useCountryKeyNameMap.
 * @param {Object.<string,TimeRow>} options.timesByCountry Country-keyed map of shipping time rows.
 * @return {DataViewsConfig} DataViews fields and pre-formatted rows.
 */
const buildDefaultConfig = ( { markets, countryNames, timesByCountry } ) => {
	const fields = [ ALL_FIELDS.market, ALL_FIELDS.shippingTime ];

	const data = markets.map( ( market ) => {
		let country = market.country;
		if ( isPrimaryMarket( market ) && market.countries?.length > 0 ) {
			country = market.countries[ 0 ];
		}

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
			shipping_time_config: timesByCountry[ country ],
		};
	} );

	return { fields, data };
};

/**
 * Adds a `marketSearchValue` to primary-market rows so the Market column's
 * global search also matches on the names of countries grouped under the
 * primary market (rendered as e.g. "Primary Market (3 countries)", which
 * otherwise hides those country names from search).
 *
 * @param {Array.<Object>} data Pre-formatted rows from a scenario builder.
 * @param {Object.<string,string>} countryNames Mapping of country code to country name.
 * @return {Array.<Object>} Rows with `marketSearchValue` added to primary-market rows.
 */
const withMarketSearchValue = ( data, countryNames ) =>
	data.map( ( row ) => {
		if ( ! isPrimaryMarket( row ) || ! row.countries?.length ) {
			return row;
		}

		const countryLabels = row.countries
			.map( ( code ) => countryNames[ code ] || code )
			.join( ' ' );

		return {
			...row,
			marketSearchValue: `${ row.label } ${ countryLabels }`,
		};
	} );

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
	const countryNames = useCountryKeyNameMap();
	const { settings } = useSettings();
	const { data: shippingRatesData, hasFinishedResolution: hasResolvedRates } =
		useShippingRates();
	const { data: shippingTimesData, hasFinishedResolution: hasResolvedTimes } =
		useShippingTimes();

	const hasFinishedResolution =
		hasResolvedMarkets &&
		hasResolvedRates &&
		hasResolvedTimes &&
		!! settings;

	if ( ! hasFinishedResolution ) {
		return { fields: [], data: [], hasFinishedResolution };
	}

	const shippingRateMethod = settings.shipping_rate;

	if ( shippingRateMethod === SHIPPING_RATE_METHOD.MANUAL ) {
		const { fields, data } = buildManualConfig( { markets } );
		return {
			fields,
			data: withMarketSearchValue( data, countryNames ),
			hasFinishedResolution,
		};
	}

	const timesByCountry = Object.fromEntries(
		( shippingTimesData || [] ).map( ( time ) => [
			time.countryCode,
			time,
		] )
	);

	if ( shippingRateMethod === SHIPPING_RATE_METHOD.AUTOMATIC ) {
		const { fields, data } = buildAutomaticConfig( {
			markets,
			timesByCountry,
		} );
		return {
			fields,
			data: withMarketSearchValue( data, countryNames ),
			hasFinishedResolution,
		};
	}

	const ratesByCountry = Object.fromEntries(
		( shippingRatesData || [] ).map( ( rate ) => [ rate.country, rate ] )
	);
	if ( shippingRateMethod === SHIPPING_RATE_METHOD.FLAT ) {
		const { fields, data } = buildFlatConfig( {
			markets,
			ratesByCountry,
			timesByCountry,
		} );
		return {
			fields,
			data: withMarketSearchValue( data, countryNames ),
			hasFinishedResolution,
		};
	}

	const { fields, data } = buildDefaultConfig( {
		markets,
		countryNames,
		timesByCountry,
	} );
	return {
		fields,
		data: withMarketSearchValue( data, countryNames ),
		hasFinishedResolution,
	};
};

export default useMarketDataViewsConfig;
