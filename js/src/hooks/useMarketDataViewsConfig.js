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

const isPrimaryMarket = ( market ) => market.id === PRIMARY_MARKET_ID;

/**
 * Field definition shared across scenarios for the Market column.
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
 * @param {{ currency: string, rate: number }|undefined} rateRow
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
 * @param {{ time: number, maxTime: number }|undefined} timeRow
 * @return {string} Formatted days string or '-'.
 */
const formatShippingTime = ( timeRow ) => {
	if ( ! timeRow ) {
		return '-';
	}
	const { time, maxTime } = timeRow;
	if ( time === maxTime ) {
		return sprintf(
			// translators: %d: number of shipping days.
			__( '%d days', 'google-listings-and-ads' ),
			time
		);
	}
	return sprintf(
		// translators: 1: minimum shipping days, 2: maximum shipping days.
		__( '%1$d-%2$d days', 'google-listings-and-ads' ),
		time,
		maxTime
	);
};

/**
 * @param {{ rate: number, currency: string, options?: { free_shipping_threshold?: number } }|undefined} rateRow
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
 * Manual shipping scenario: Market, Country (count), Shipping (static "Managed in Google").
 * Only the primary market row is shown.
 *
 * @param {Object} options
 * @param {Object} options.primaryMarket Primary market data from usePrimaryMarketDetails.
 * @return {{ fields: Array, data: Array }} DataViews fields and pre-formatted rows.
 */
const buildManualConfig = ( { primaryMarket } ) => {
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
 * @param {Object} options
 * @param {Array}  options.markets         All markets from useMarkets.
 * @param {Object} options.ratesByCountry  Country-keyed map of shipping rate rows.
 * @param {Object} options.timesByCountry  Country-keyed map of shipping time rows.
 * @return {{ fields: Array, data: Array }} DataViews fields and pre-formatted rows.
 */
const buildFlatConfig = ( { markets, ratesByCountry, timesByCountry } ) => {
	const fields = [
		ALL_FIELDS.market,
		ALL_FIELDS.shippingRate,
		ALL_FIELDS.shippingTime,
		ALL_FIELDS.freeShipping,
	];

	const data = markets.map( ( market ) => {
		const rateRow = ratesByCountry[ market.country ];
		const timeRow = timesByCountry[ market.country ];
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
 * @param {Object}  options
 * @param {Array}   options.markets             All markets from useMarkets.
 * @param {Object}  options.primaryMarket       Primary market data from usePrimaryMarketDetails.
 * @param {boolean} options.isMultiLingualStore Whether the store has a multilingual plugin.
 * @param {Object}  options.timesByCountry      Country-keyed map of shipping time rows.
 * @return {{ fields: Array, data: Array }} DataViews fields and pre-formatted rows.
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
 * any scenario that doesn't yet have a dedicated builder.
 *
 * TODO: Remove once all scenarios have explicit branches:
 *   - GOOWOO-598 / -602: remaining multilingual variants.
 *
 * @param {Object} options
 * @param {Array}  options.markets      All markets from useMarkets.
 * @param {Object}  options.countryNames Mapping of country code to country name from useCountryKeyNameMap.
 * @return {{ fields: Array, data: Array }} DataViews fields and pre-formatted rows.
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
 * @return {{ fields: Array, data: Array, hasFinishedResolution: boolean }} DataViews fields and pre-formatted rows.
 */
const useMarketDataViewsConfig = () => {
	const { data: markets, hasFinishedResolution: marketsResolved } =
		useMarkets();
	const { data: primaryMarket } = usePrimaryMarketDetails();
	const countryNames = useCountryKeyNameMap();
	const { settings } = useSettings();
	const { data: shippingRatesData, hasFinishedResolution: ratesResolved } =
		useShippingRates();
	const { data: shippingTimesData, hasFinishedResolution: timesResolved } =
		useShippingTimes();

	const isMultiLingualStore = glaData.isMultiLingualStore ?? false;

	const hasFinishedResolution =
		marketsResolved && ratesResolved && timesResolved;

	if ( ! hasFinishedResolution || ! settings ) {
		return { fields: [], data: [], hasFinishedResolution };
	}

	const shippingRate = settings?.shipping_rate;

	if ( shippingRate === SHIPPING_RATE_METHOD.MANUAL ) {
		return {
			...buildManualConfig( { primaryMarket } ),
			hasFinishedResolution,
		};
	}

	const ratesByCountry = Object.fromEntries(
		( shippingRatesData || [] ).map( ( r ) => [ r.country, r ] )
	);
	const timesByCountry = Object.fromEntries(
		( shippingTimesData || [] ).map( ( t ) => [ t.countryCode, t ] )
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
