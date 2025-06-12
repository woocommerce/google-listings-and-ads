/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { isEqual } from 'lodash';
import { withViewportMatch } from '@wordpress/viewport';
import { useState, useMemo, useEffect, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { recordGlaEvent } from '~/utils/tracks';
import EmptyMetricsNotice from '../empty-metrics-notice';
import usePriceBenchmarkSuggestions from '~/hooks/usePriceBenchmarkSuggestions';
import ChangePrice from '../change-price';
import EffectivenessIndicator from '../effectiveness-indicator';
import Label from '../label';
import Price from '../price';
import FaqLink from '../faq-link';
import {
	PRICE_BENCHMARK_SUGGESTIONS_CONTEXT,
	LABELS,
	LABEL_CHANGE_EFFECTIVENESS,
	LABEL_AVG_PRICE_ON_GOOGLE,
	LABEL_PRICE_GAP_PERCENT,
	LABEL_SUGGESTED_PRICE,
	LABEL_REGULAR_PRICE,
	LABEL_ACTION,
} from '../constants';
import './index.scss';

const PRODUCT_TABLE_FIELDS = [
	{
		id: 'image',
		enableHiding: false,
		enableSorting: false,
		enableGlobalSearch: false,
		label: __( 'Image', 'google-listings-and-ads' ),
		getValue: ( { item } ) => {
			return item?.product?.thumbnail || null;
		},
		render: ( { item } ) => {
			if ( ! item?.product?.thumbnail ) {
				return null;
			}

			return (
				<img
					src={ item.product.thumbnail }
					alt={ item.product.title }
					className="gla-price-benchmark-suggestions__image"
				/>
			);
		},
	},
	{
		id: 'id',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: true,
		label: __( 'Product', 'google-listings-and-ads' ),
		getValue: ( { item } ) => {
			return item.product.id;
		},
		render: ( { item } ) => {
			if ( ! item?.product?.title ) {
				return null;
			}

			return item.product.title;
		},
	},
	{
		id: 'description',
		enableHiding: false,
		enableSorting: false,
		enableGlobalSearch: true,
		label: __( 'Description', 'google-listings-and-ads' ),
		getValue: ( { item } ) => {
			// Cast the id to string for search functionality to work properly.
			return String( item?.product?.id || '' );
		},
	},
];

const METRICS_TABLE_FIELDS = [
	{
		id: 'effectiveness',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: false,
		header: <Label labelKey={ LABEL_CHANGE_EFFECTIVENESS } alignLeft />,
		label: LABELS[ LABEL_CHANGE_EFFECTIVENESS ].title,
		render: ( { item } ) => {
			if ( item.effectiveness === undefined ) {
				return null;
			}

			return (
				<EffectivenessIndicator effectiveness={ item.effectiveness } />
			);
		},
	},
	{
		id: 'product_price',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: false,
		label: LABELS[ LABEL_REGULAR_PRICE ].title,
		render: ( { item } ) => {
			return <Price amount={ item.product_price } highlight />;
		},
	},
	{
		id: 'benchmark_price',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: false,
		header: <Label labelKey={ LABEL_AVG_PRICE_ON_GOOGLE } />,
		label: LABELS[ LABEL_AVG_PRICE_ON_GOOGLE ].title,
		render: ( { item } ) => {
			return <Price amount={ item.benchmark_price } />;
		},
	},
	{
		id: 'price_gap',
		enableHiding: false,
		enableSorting: false,
		enableGlobalSearch: false,
		header: <Label labelKey={ LABEL_PRICE_GAP_PERCENT } />,
		label: LABELS[ LABEL_PRICE_GAP_PERCENT ].title,
		render: ( { item } ) => {
			return `${ item.price_gap || 0 }%`;
		},
	},
	{
		id: 'suggested_price',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: false,
		header: <Label labelKey={ LABEL_SUGGESTED_PRICE } />,
		label: LABELS[ LABEL_SUGGESTED_PRICE ].title,
		render: ( { item } ) => {
			return <Price amount={ item.suggested_price } />;
		},
	},
	{
		id: 'action',
		enableHiding: false,
		enableSorting: false,
		enableGlobalSearch: false,
		header: (
			<span className="gla-price-benchmark-suggestions__action">
				{ LABELS[ LABEL_ACTION ].title }
			</span>
		),
		label: LABELS[ LABEL_ACTION ].title,
		render: ( { item } ) => {
			return (
				<div className="gla-price-benchmark-suggestions__action">
					<ChangePrice productId={ item?.product?.id } />
				</div>
			);
		},
	},
];

const TABLE_FIELDS_MOBILE = [ 'action' ];
const DEFAULT_QUERY_PARAMS = {
	search: '',
	page: 1,
	perPage: 10,
	sort: {
		direction: 'desc',
		field: 'effectiveness',
	},
};

/**
 * @event gla_price_benchmarks_shown
 * @property {string} context The context of the event.
 * @property {number} suggestions The number of suggestions shown.
 */

/**
 * PriceBenchmarkSuggestions component.
 *
 * This component fetches and displays price benchmark suggestions using the
 * `usePriceBenchmarkSuggestions` hook. It renders a table with the suggestions
 * data and handles responsiveness by providing different field configurations
 * for desktop and mobile views.
 *
 * @fires gla_price_benchmarks_shown with `{ context: 'price-benchmark-suggestions' }` and the suggestions count.
 *
 * @param {Object} props - The component props.
 * @param {boolean} props.isViewportMobile - Indicates if the viewport is in mobile size.
 * @return {JSX.Element} A div containing the DataViews component.
 */
const PriceBenchmarkSuggestions = ( { isViewportMobile } ) => {
	const { DataViews } = window.wp.dataviews;
	const [ view, setView ] = useState( {
		type: 'table',
		layout: {},
		fields: [],
		filters: [],
		titleField: 'id',
		descriptionField: 'description',
		mediaField: 'image',
		...DEFAULT_QUERY_PARAMS,
	} );

	const updatedQueryParams = {
		order: view.sort.direction,
		orderby: view.sort.field,
		search: view.search,
		page: view.page,
		per_page: view.perPage,
	};
	const {
		data: { items: suggestions, meta },
		hasFinishedResolution,
	} = usePriceBenchmarkSuggestions( updatedQueryParams );

	const handleOnChangeView = useCallback( ( newView ) => {
		setView( newView );
	}, [] );

	// Determine the fields to be displayed based on the viewport size.
	const viewportFields = useMemo( () => {
		if ( isViewportMobile ) {
			return TABLE_FIELDS_MOBILE;
		}
		return METRICS_TABLE_FIELDS.map( ( field ) => field.id );
	}, [ isViewportMobile ] );

	useEffect( () => {
		setView( ( prevView ) => ( {
			...prevView,
			fields: viewportFields,
		} ) );
	}, [ viewportFields ] );

	useEffect( () => {
		if ( ! hasFinishedResolution ) {
			return;
		}

		recordGlaEvent( 'gla_price_benchmarks_shown', {
			context: PRICE_BENCHMARK_SUGGESTIONS_CONTEXT,
			suggestions: suggestions.length,
			per_page: view.perPage,
		} );
	}, [ hasFinishedResolution, suggestions, view ] );

	// If there are no suggestions and the query params are default, which means we are loading the initial set of data, show an empty notice.
	if (
		hasFinishedResolution &&
		! suggestions?.length &&
		isEqual(
			{
				search: updatedQueryParams.search,
				page: updatedQueryParams.page,
				perPage: updatedQueryParams.per_page,
				sort: {
					direction: updatedQueryParams.order,
					field: updatedQueryParams.orderby,
				},
			},
			DEFAULT_QUERY_PARAMS
		)
	) {
		return <EmptyMetricsNotice />;
	}

	return (
		<div className="gla-price-benchmark-suggestions">
			<DataViews
				getItemId={ ( item ) => item?.product?.id }
				fields={ [ ...PRODUCT_TABLE_FIELDS, ...METRICS_TABLE_FIELDS ] }
				data={ suggestions }
				view={ view }
				paginationInfo={ {
					totalItems: meta?.totalItems,
					totalPages: Math.ceil( meta?.totalItems / view?.perPage ),
				} }
				onChangeView={ handleOnChangeView }
				defaultLayouts={ [] }
				header={ <FaqLink /> }
				isLoading={ ! hasFinishedResolution }
			/>
		</div>
	);
};

export default withViewportMatch( {
	isViewportMobile: '< medium',
} )( PriceBenchmarkSuggestions );
