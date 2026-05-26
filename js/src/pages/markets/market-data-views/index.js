/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useMemo } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { Icon, edit, trash } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import applyEqualColumnWidths from '~/utils/applyEqualColumnWidths';
import { PRIMARY_MARKET_ID } from '../constants';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useMarketDataViewsConfig from '~/hooks/useMarketDataViewsConfig';
import EditMarketModal from '../edit-market-modal';
import DeleteMarketModal from '../delete-market-modal';
import './index.scss';

const isPrimaryMarket = ( market ) => market.id === PRIMARY_MARKET_ID;

const DEFAULT_VIEW = {
	type: 'table',
	page: 1,
	perPage: 10,
};

/**
 * Markets data table.
 *
 * Thin shell over `@wordpress/dataviews`. Scenario-specific field/data shapes
 * live in `useMarketDataViewsConfig`; this component only owns the modal state
 * and the per-row actions.
 */
const MarketDataViews = () => {
	const { DataViews } = window.wp.dataviews;
	const { fields, data, hasFinishedResolution } = useMarketDataViewsConfig();
	const [ view, setView ] = useState( DEFAULT_VIEW );
	const [ editingMarket, setEditingMarket ] = useState( null );
	const [ deletingMarket, setDeletingMarket ] = useState( null );
	const { targetAudience, loaded } = useTargetAudienceFinalCountryCodes();

	// `view.fields` is the list of visible columns; it must track the field set
	// returned by `useMarketDataViewsConfig`, which varies per scenario.
	// Derive it inline so a scenario change (e.g. the markets resolver landing
	// after first render) updates the visible columns. The user's view-state
	// changes (sorting, pagination) still flow through `setView`.
	const fieldIds = fields.map( ( field ) => field.id );
	const viewWithFields = applyEqualColumnWidths(
		{ ...view, fields: fieldIds },
		fieldIds
	);

	const ACTIONS = useMemo(
		() => [
			{
				id: 'edit',
				label: () => (
					<span className="gla-market-data-views__button-label">
						{ loaded ? (
							<Icon icon={ edit } width={ 16 } height={ 16 } />
						) : (
							<Spinner />
						) }
						{ __( 'Edit', 'google-listings-and-ads' ) }
					</span>
				),
				isPrimary: true,
				callback: loaded
					? ( [ market ] ) => setEditingMarket( market )
					: () => {},
			},
			{
				id: 'delete',
				label: () => (
					<span className="gla-market-data-views__button-label">
						<Icon icon={ trash } width={ 16 } height={ 16 } />
						{ __( 'Delete', 'google-listings-and-ads' ) }
					</span>
				),
				isDestructive: true,
				isEligible: ( market ) => ! isPrimaryMarket( market ),
				callback: ( [ market ] ) => setDeletingMarket( market ),
			},
		],
		[ loaded ]
	);

	return (
		<>
			<div className="gla-market-data-views">
				<DataViews
					getItemId={ ( item ) => item.id }
					fields={ fields }
					actions={ ACTIONS }
					data={ data }
					view={ viewWithFields }
					onChangeView={ setView }
					paginationInfo={ {
						totalItems: data.length,
						totalPages: 1,
					} }
					defaultLayouts={ { table: {} } }
					isLoading={ ! hasFinishedResolution }
				/>
			</div>

			{ editingMarket && (
				<EditMarketModal
					market={ editingMarket }
					onRequestClose={ () => setEditingMarket( null ) }
					targetAudience={ targetAudience }
				/>
			) }

			{ deletingMarket && (
				<DeleteMarketModal
					market={ deletingMarket }
					onRequestClose={ () => setDeletingMarket( null ) }
				/>
			) }
		</>
	);
};

export default MarketDataViews;
