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
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
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
	const { targetAudience, loaded } = useTargetAudienceFinalCountryCodes();

	// `view.fields` is the list of visible columns; it must track the field set
	// returned by `useMarketDataViewsConfig`, which varies per scenario.
	// Derive it inline so a scenario change (e.g. the markets resolver landing
	// after first render) updates the visible columns. The user's view-state
	// changes (sorting, pagination) still flow through `setView`.
	const viewWithFields = useMemo(
		() => ( {
			...view,
			fields: fields.map( ( field ) => field.id ),
		} ),
		[ view, fields ]
	);

	const { data: processedData, paginationInfo } = useMemo(
		() => filterSortAndPaginate( data, viewWithFields, fields ),
		[ data, viewWithFields, fields, filterSortAndPaginate ]
	);

	const ACTIONS = useMemo(
		() => [
			{
				id: 'edit',
				label: __( 'Edit', 'google-listings-and-ads' ),
				icon: loaded ? (
					<Icon icon={ edit } width={ 24 } height={ 24 } />
				) : (
					<Spinner />
				),
				isPrimary: true,
				callback: loaded
					? ( [ market ] ) => setEditingMarket( market )
					: () => {},
			},
			{
				id: 'delete',
				label: __( 'Delete', 'google-listings-and-ads' ),
				icon: <Icon icon={ trash } width={ 24 } height={ 24 } />,
				isDestructive: true,
				isEligible: ( market ) => ! isPrimaryMarket( market ),
				callback: ( [ market ] ) => setDeletingMarket( market ),
			},
		],
		[ loaded ]
	);

	return (
		<>
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
