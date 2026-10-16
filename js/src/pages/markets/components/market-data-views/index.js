/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useMemo } from '@wordpress/element';
import { Icon, edit, trash } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import applyEqualColumnWidths from '~/utils/applyEqualColumnWidths';
import useMarketDataViewsConfig from '../../hooks/useMarketDataViewsConfig';
import EditMarketModal from '../edit-market-modal';
import DeleteMarketModal from '../delete-market-modal';
import isPrimaryMarket from '../../utils/isPrimaryMarket';
import './index.scss';

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
	const { DataViews, filterSortAndPaginate } = window.wp.dataviews;
	const { fields, data, hasFinishedResolution } = useMarketDataViewsConfig();
	const [ view, setView ] = useState( DEFAULT_VIEW );
	const [ editingMarket, setEditingMarket ] = useState( null );
	const [ deletingMarket, setDeletingMarket ] = useState( null );

	// `view.fields` is the list of visible columns; it must track the field set
	// returned by `useMarketDataViewsConfig`, which varies per scenario.
	// Derive it inline so a scenario change (e.g. the markets resolver landing
	// after first render) updates the visible columns. The user's view-state
	// changes (sorting, pagination) still flow through `setView`.
	const viewWithFields = useMemo( () => {
		const fieldIds = fields.map( ( field ) => field.id );
		return applyEqualColumnWidths(
			{ ...view, fields: fieldIds },
			fieldIds
		);
	}, [ view, fields ] );

	const { data: processedData, paginationInfo } = useMemo(
		() => filterSortAndPaginate( data, viewWithFields, fields ),
		[ data, viewWithFields, fields, filterSortAndPaginate ]
	);

	const ACTIONS = useMemo(
		() => [
			{
				id: 'edit',
				label: () => (
					<span className="gla-market-data-views__button-label">
						<Icon icon={ edit } width={ 16 } height={ 16 } />
						{ __( 'Edit', 'google-listings-and-ads' ) }
					</span>
				),
				isPrimary: true,
				callback: ( [ market ] ) => setEditingMarket( market ),
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
		[]
	);

	return (
		<>
			<div className="gla-market-data-views">
				<DataViews
					getItemId={ ( item ) => item.id }
					fields={ fields }
					actions={ ACTIONS }
					data={ processedData }
					view={ viewWithFields }
					onChangeView={ setView }
					paginationInfo={ paginationInfo }
					defaultLayouts={ { table: {} } }
					isLoading={ ! hasFinishedResolution }
				/>
			</div>

			{ editingMarket && (
				<EditMarketModal
					market={ editingMarket }
					onRequestClose={ () => setEditingMarket( null ) }
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
