/**
 * External dependencies
 */
import { useState, useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useProgramsDataViewsConfig, {
	PMAX_TITLE_CLASS_NAME,
} from './useProgramsDataViewsConfig';
import useProgramActions from './actions';
import CampaignAssetsTour from '~/components/tours/campaign-assets-tour';
import { CAMPAIGN_TYPE_PMAX } from '~/constants';
import { recordGlaEvent } from '~/utils/tracks';

const DEFAULT_VIEW = {
	type: 'table',
	page: 1,
	perPage: 25,
	// DataViews disables the rows-per-page picker when the view has no sort field.
	sort: { field: 'title', direction: 'asc' },
	fields: [ 'country', 'budget', 'status' ],
	titleField: 'title',
};

const DEFAULT_LAYOUTS = { table: {} };

const LEGACY_TRACKING_COLUMN_NAMES = {
	title: 'id',
	country: 'country',
	budget: 'dailyBudget',
	status: 'enabled',
};

const CAMPAIGN_ASSETS_TOUR_SELECTOR = `.gla-all-programs-table-card tr:has(.${ PMAX_TITLE_CLASS_NAME }) .dataviews-view-table__actions-column`;

/**
 * Records the tracking events of the view changes made by the merchant.
 *
 * @param {string} report Name of the report table.
 * @param {Object} prevView The view before the change.
 * @param {Object} nextView The view after the change.
 */
const recordViewChangeEvents = ( report, prevView, nextView ) => {
	const { sort } = nextView;

	if (
		sort &&
		( sort.field !== prevView.sort?.field ||
			sort.direction !== prevView.sort?.direction )
	) {
		recordGlaEvent( 'gla_table_sort', {
			report,
			column: LEGACY_TRACKING_COLUMN_NAMES[ sort.field ],
			direction: sort.direction,
		} );
	}

	const prevFields = prevView.fields ?? [];
	const nextFields = nextView.fields ?? [];
	const toggledFields = [
		...nextFields.filter( ( field ) => ! prevFields.includes( field ) ),
		...prevFields.filter( ( field ) => ! nextFields.includes( field ) ),
	];

	toggledFields.forEach( ( field ) => {
		recordGlaEvent( 'gla_table_header_toggle', {
			report,
			column: LEGACY_TRACKING_COLUMN_NAMES[ field ],
			status: nextFields.includes( field ) ? 'on' : 'off',
		} );
	} );
};

/**
 * DataViews table of the free listings program and the paid campaigns.
 * It requires `window.wp.dataviews` to be loaded.
 *
 * @fires gla_table_sort with `report: trackEventReportId` and the legacy column name when the sorting changes.
 * @fires gla_table_header_toggle with `report: trackEventReportId` and the legacy column name when a column is shown or hidden.
 *
 * @param {Object} props React props.
 * @param {string} [props.trackEventReportId] Report ID used in tracking events. No events are recorded when it's not set.
 */
const ProgramsDataViews = ( { trackEventReportId } ) => {
	const { DataViews, filterSortAndPaginate } = window.wp.dataviews;
	const { fields, items, isLoading } = useProgramsDataViewsConfig();
	const actions = useProgramActions();
	const [ view, setView ] = useState( DEFAULT_VIEW );

	// Removing the last program of the last page would leave the view on an empty page,
	// and DataViews hides the pagination when there is only one page.
	const lastPage = Math.max( 1, Math.ceil( items.length / view.perPage ) );
	const effectiveView = useMemo(
		() => ( view.page > lastPage ? { ...view, page: lastPage } : view ),
		[ view, lastPage ]
	);

	const { data, paginationInfo } = useMemo(
		() => filterSortAndPaginate( items, effectiveView, fields ),
		[ filterSortAndPaginate, items, effectiveView, fields ]
	);

	const handleChangeView = ( nextView ) => {
		if ( trackEventReportId ) {
			recordViewChangeEvents(
				trackEventReportId,
				effectiveView,
				nextView
			);
		}
		setView( nextView );
	};

	const hasPmaxCampaignOnPage = data.some(
		( item ) => item.type === CAMPAIGN_TYPE_PMAX
	);

	return (
		<>
			{ hasPmaxCampaignOnPage && (
				<CampaignAssetsTour
					referenceElementCssSelector={
						CAMPAIGN_ASSETS_TOUR_SELECTOR
					}
				/>
			) }
			<DataViews
				getItemId={ ( item ) => String( item.id ) }
				fields={ fields }
				actions={ actions }
				data={ data }
				view={ effectiveView }
				onChangeView={ handleChangeView }
				paginationInfo={ paginationInfo }
				defaultLayouts={ DEFAULT_LAYOUTS }
				search={ false }
				isLoading={ isLoading }
			/>
		</>
	);
};

export default ProgramsDataViews;
