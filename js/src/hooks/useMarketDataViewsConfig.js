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
		label: __( 'Shipping time', 'google-listings-and-ads' ),
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
 * Formats a free-shipping threshold number into a localised "Free over X" string,
 * or returns a dash when no threshold is set.
 *
 * @param {number|null} threshold The threshold amount from market.free_shipping.
 * @param {string}      currency  ISO 4217 currency code from market.currency.
 * @return {string} Formatted string or '-'.
 */
const formatFreeShipping = ( threshold, currency ) => {
	if ( threshold === null || threshold === undefined ) {
		return '-';
	}
	const formatted = new Intl.NumberFormat( undefined, {
		style: 'currency',
		currency,
	} ).format( threshold );
	return sprintf(
		// translators: %s: currency-formatted free shipping threshold, e.g. "$50.00".
		__( 'Free over %s', 'google-listings-and-ads' ),
		formatted
	);
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
 * @param {Array}  options.markets All markets from useMarkets.
 * @return {{ fields: Array, data: Array }} DataViews fields and pre-formatted rows.
 */
const buildFlatConfig = ( { markets } ) => {
	const fields = [
		ALL_FIELDS.market,
		ALL_FIELDS.shippingRate,
		ALL_FIELDS.shippingTime,
		ALL_FIELDS.freeShipping,
	];

	const data = markets.map( ( market ) => ( {
		...market,
		freeShipping: formatFreeShipping(
			market.free_shipping,
			market.currency
		),
	} ) );

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
 * @return {{ fields: Array, data: Array }} DataViews fields and pre-formatted rows.
 */
const buildAutomaticConfig = ( {
	markets,
	primaryMarket,
	isMultiLingualStore,
} ) => {
	if ( isMultiLingualStore ) {
		const fields = [
			ALL_FIELDS.market,
			ALL_FIELDS.language,
			ALL_FIELDS.currency,
			ALL_FIELDS.shippingTime,
		];

		return { fields, data: markets };
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
 * @param {Array}  markets      All markets from useMarkets.
 * @param {Object} countryNames Country code → name lookup from useCountryKeyNameMap.
 * @return {{ fields: Array, data: Array }} DataViews fields and pre-formatted rows.
 */
const buildDefaultConfig = ( markets, countryNames ) => {
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
	const { data: markets, hasFinishedResolution } = useMarkets();
	const { data: primaryMarket } = usePrimaryMarketDetails();
	const countryNames = useCountryKeyNameMap();
	const { settings } = useSettings();

	const isMultiLingualStore = glaData.isMultiLingualStore ?? false;

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

	if ( shippingRate === SHIPPING_RATE_METHOD.FLAT ) {
		return {
			...buildFlatConfig( { markets } ),
			hasFinishedResolution,
		};
	}

	if ( shippingRate === SHIPPING_RATE_METHOD.AUTOMATIC ) {
		return {
			...buildAutomaticConfig( {
				markets,
				primaryMarket,
				isMultiLingualStore,
			} ),
			hasFinishedResolution,
		};
	}

	return {
		...buildDefaultConfig( markets, countryNames ),
		hasFinishedResolution,
	};
};

export default useMarketDataViewsConfig;
