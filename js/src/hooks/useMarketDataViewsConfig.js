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
 * Fall-through default — preserves the legacy Market + Shipping times shape so
 * scenarios that don't have a dedicated branch yet still render correctly.
 *
 * TODO: Replace per-scenario as the remaining scenario tickets land:
 *   - GOOWOO-582: flat shipping, no multilingual.
 *   - GOOWOO-586: automatic shipping, no multilingual.
 *   - GOOWOO-598 / -602 / -606: multilingual variants of each shipping mode.
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
 * `glaData.multiLingualStore`, formats the rows, and returns the DataViews-ready
 * config. `MarketDataViews` consumes this directly with no scenario branching of
 * its own.
 *
 * @return {{ fields: Array, data: Array }} DataViews fields and pre-formatted rows.
 */
const useMarketDataViewsConfig = () => {
	const { data: markets, hasFinishedResolution } = useMarkets();
	const { data: primaryMarket } = usePrimaryMarketDetails();
	const countryNames = useCountryKeyNameMap();

	// TODO: `glaData.multiLingualStore` is not yet populated by Admin.php — the
	// backend wiring lands with the multilingual scenario tickets (GOOWOO-598 /
	// -602 / -606). Until then this always evaluates to `false`, which is
	// correct behaviour: multilingual scenarios are unreachable.
	const multiLingualStore = glaData.multiLingualStore ?? false;

	const shippingRate = primaryMarket?.shipping_rate;

	if ( ! multiLingualStore && shippingRate === SHIPPING_RATE_METHOD.MANUAL ) {
		return { ...buildManualConfig( primaryMarket ), hasFinishedResolution };
	}

	return {
		...buildDefaultConfig( markets, countryNames ),
		hasFinishedResolution,
	};
};

export default useMarketDataViewsConfig;
