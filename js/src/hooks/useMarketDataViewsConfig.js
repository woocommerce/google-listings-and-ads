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

const isPrimaryMarket = ( market ) => market.id === PRIMARY_MARKET_ID;

/**
 * Field definition shared across scenarios for the Market column.
 */
const marketField = {
	id: 'market',
	label: __( 'Market', 'google-listings-and-ads' ),
	enableHiding: false,
	enableSorting: false,
	render: ( { item } ) => (
		<span className="gla-markets-table__market-cell">{ item.label }</span>
	),
};

/**
 * Manual + non-multilingual scenario: Market, Country (count), Shipping (static).
 */
const buildManualConfig = ( primaryMarket ) => {
	const countryCount = primaryMarket?.countries?.length ?? 0;

	const fields = [
		marketField,
		{
			id: 'country',
			label: __( 'Country', 'google-listings-and-ads' ),
			enableHiding: false,
			enableSorting: false,
		},
		{
			id: 'shipping',
			label: __( 'Shipping', 'google-listings-and-ads' ),
			enableHiding: false,
			enableSorting: false,
		},
	];

	const data = primaryMarket
		? [
				{
					...primaryMarket,
					label: primaryMarket.label,
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
 * Automatic shipping + non-multilingual scenario: Market (label + country count), Shipping time.
 * Only the primary market row is shown.
 */
const buildAutomaticConfig = ( primaryMarket ) => {
	const countryCount = primaryMarket?.countries?.length ?? 0;

	const fields = [
		marketField,
		{
			id: 'shippingTime',
			label: __( 'Shipping time', 'google-listings-and-ads' ),
			enableHiding: false,
			enableSorting: false,
		},
	];

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
 * Formats a free-shipping threshold number into a localised "Free over X" string,
 * or returns null when no threshold is set.
 *
 * @param {number|null} threshold The threshold amount from market.free_shipping.
 * @param {string}      currency  ISO 4217 currency code from market.currency.
 * @return {string|null} Formatted string or null.
 */
const formatFreeShipping = ( threshold, currency ) => {
	if ( threshold === null || threshold === undefined ) {
		return null;
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
 * Flat shipping + non-multilingual scenario: Market, Shipping Rate, Shipping Time, Free shipping.
 * All markets (primary and additional) appear as rows.
 */
const buildFlatConfig = ( markets ) => {
	const fields = [
		marketField,
		{
			id: 'shippingRate',
			label: __( 'Shipping rate', 'google-listings-and-ads' ),
			enableHiding: false,
			enableSorting: false,
		},
		{
			id: 'shippingTime',
			label: __( 'Shipping time', 'google-listings-and-ads' ),
			enableHiding: false,
			enableSorting: false,
		},
		{
			id: 'freeShipping',
			label: __( 'Free shipping', 'google-listings-and-ads' ),
			enableHiding: false,
			enableSorting: false,
		},
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
 * Multilingual + automatic shipping scenario: Market, Language, Currency, Shipping time.
 * All markets (primary and additional) appear as rows.
 */
const buildMultiLingualAutomaticConfig = ( markets ) => {
	const fields = [
		marketField,
		{
			id: 'language',
			label: __( 'Language', 'google-listings-and-ads' ),
			enableHiding: false,
			enableSorting: false,
		},
		{
			id: 'currency',
			label: __( 'Currency', 'google-listings-and-ads' ),
			enableHiding: false,
			enableSorting: false,
		},
		{
			id: 'shippingTime',
			label: __( 'Shipping time', 'google-listings-and-ads' ),
			enableHiding: false,
			enableSorting: false,
		},
	];

	const data = markets.map( ( market ) => ( { ...market } ) );

	return { fields, data };
};

/**
 * Fall-through default — preserves the legacy Market + Shipping times shape so
 * scenarios that don't have a dedicated branch yet still render correctly.
 *
 * TODO: Replace per-scenario as the remaining scenario tickets land:
 *   - GOOWOO-598 / -602: remaining multilingual variants.
 * Once all scenarios have explicit branches, this default should be removed.
 */
const buildDefaultConfig = ( markets, countryNames ) => {
	const fields = [
		marketField,
		{
			id: 'shippingTime',
			label: __( 'Shipping times', 'google-listings-and-ads' ),
			enableHiding: false,
			enableSorting: false,
		},
	];

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
 * Picks the active scenario from the primary market's `shipping_rate` and
 * `glaData.isMultiLingualStore`, formats the rows, and returns the DataViews-ready
 * config. `MarketDataViews` consumes this directly with no scenario branching of
 * its own.
 *
 * @return {{ fields: Array, data: Array }} DataViews fields and pre-formatted rows.
 */
const useMarketDataViewsConfig = () => {
	const { data: markets, hasFinishedResolution } = useMarkets();
	const { data: primaryMarket } = usePrimaryMarketDetails();
	const countryNames = useCountryKeyNameMap();

	const isMultiLingualStore = glaData.isMultiLingualStore ?? false;

	const shippingRate = primaryMarket?.shipping_rate;

	if (
		! isMultiLingualStore &&
		shippingRate === SHIPPING_RATE_METHOD.MANUAL
	) {
		return { ...buildManualConfig( primaryMarket ), hasFinishedResolution };
	}

	if ( ! isMultiLingualStore && shippingRate === SHIPPING_RATE_METHOD.FLAT ) {
		return {
			...buildFlatConfig( markets ),
			hasFinishedResolution,
		};
	}

	if (
		! isMultiLingualStore &&
		shippingRate === SHIPPING_RATE_METHOD.AUTOMATIC
	) {
		return {
			...buildAutomaticConfig( primaryMarket ),
			hasFinishedResolution,
		};
	}

	if (
		isMultiLingualStore &&
		shippingRate === SHIPPING_RATE_METHOD.AUTOMATIC
	) {
		return {
			...buildMultiLingualAutomaticConfig( markets ),
			hasFinishedResolution,
		};
	}

	return {
		...buildDefaultConfig( markets, countryNames ),
		hasFinishedResolution,
	};
};

export default useMarketDataViewsConfig;
