/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { edit, trash } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { PRIMARY_MARKET_ID } from '../../constants';
import useMarkets from '~/hooks/useMarkets';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import EditMarketModal from '../edit-market-modal';
import './index.scss';

const FIELDS = [
	{
		id: 'market',
		label: __( 'Market', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
		render: ( { item } ) => (
			<span className="gla-markets-table__market-cell">
				{ item.market }
			</span>
		),
	},
	{
		id: 'shippingTime',
		label: __( 'Shipping times', 'google-listings-and-ads' ),
		enableHiding: false,
		enableSorting: false,
	},
];

const DEFAULT_VIEW = {
	type: 'table',
	fields: FIELDS.map( ( field ) => field.id ),
	page: 1,
	perPage: 10,
};

const isPrimaryMarket = ( market ) => market.id === PRIMARY_MARKET_ID;

/**
 * Markets data table.
 *
 * Renders the default two-column view (Market, Shipping times) plus a per-row
 * actions column managed by `@wordpress/dataviews`. Custom DataViews variants
 * per shipping rate / multilingual store will be added in a follow-up task;
 * `shippingRate` is accepted today but currently ignored.
 *
 * Known design / implementation mismatches we are intentionally accepting to
 * stay on the built-in DataViews UI:
 *
 * - Figma asks for an always-visible "Edit" text button. DataViews only lifts
 *   actions out of the kebab menu when both `isPrimary` and `icon` are set,
 *   and renders them as an icon-only button (the label is exposed only as
 *   `aria-label`). The built-in stylesheet additionally hides primary action
 *   buttons until the row is hovered. We therefore ship an icon-only Edit that
 *   appears on hover; matching the Figma exactly would require dropping the
 *   built-in component, which isn't worth it for now.
 * - The primary market cannot be deleted, so the Delete action is gated by
 *   `isEligible` and simply omitted on the primary row (only Edit shows).
 *   The Delete callback is a placeholder until the real deletion flow is
 *   wired up.
 *
 * @param {Object} props
 * @param {string} [props.shippingRate] One of the values defined in `SHIPPING_RATE_METHOD`.
 */
const MarketDataViews = ( { shippingRate } ) => {
	// Reserved; will drive view variants in a follow-up task.
	void shippingRate;

	const { DataViews } = window.wp.dataviews;
	const { data: markets, hasFinishedResolution } = useMarkets();
	const [ view, setView ] = useState( DEFAULT_VIEW );
	const [ editingMarket, setEditingMarket ] = useState( null );
	const { targetAudience, getFinalCountries, loaded } =
		useTargetAudienceFinalCountryCodes();

	const rows = markets.map( ( market ) => ( {
		...market,
		market: sprintf(
			// translators: 1: market label, 2: number of countries.
			_n(
				'%1$s (%2$d country)',
				'%1$s (%2$d countries)',
				market.countries.length,
				'google-listings-and-ads'
			),
			market.label,
			market.countries.length
		),
		shippingTime: market.shipping_time,
	} ) );

	const ACTIONS = [
		{
			id: 'edit',
			label: __( 'Edit', 'google-listings-and-ads' ),
			icon: loaded ? edit : <Spinner />,
			isPrimary: true,
			callback: loaded
				? ( [ market ] ) => setEditingMarket( market )
				: () => {},
		},
		{
			id: 'delete',
			label: __( 'Delete', 'google-listings-and-ads' ),
			icon: trash,
			isDestructive: true,
			isEligible: ( market ) => ! isPrimaryMarket( market ),
			callback: () => {},
		},
	];

	return (
		<>
			<DataViews
				getItemId={ ( item ) => item.id }
				fields={ FIELDS }
				actions={ ACTIONS }
				data={ rows }
				view={ view }
				onChangeView={ setView }
				paginationInfo={ {
					totalItems: rows.length,
					totalPages: 1,
				} }
				defaultLayouts={ { table: {} } }
				isLoading={ ! hasFinishedResolution }
			/>

			{ editingMarket && (
				<EditMarketModal
					market={ editingMarket }
					onRequestClose={ () => setEditingMarket( null ) }
					targetAudience={ targetAudience }
					resolveFinalCountries={ getFinalCountries }
				/>
			) }
		</>
	);
};

export default MarketDataViews;
