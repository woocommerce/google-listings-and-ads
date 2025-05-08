/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { withViewportMatch } from '@wordpress/viewport';
import { TablePlaceholder } from '@woocommerce/components';
import { useState, useMemo, useEffect, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import EmptyMetricsNotice from '../empty-metrics-notice';
import usePriceBenchmarkSuggestions from '~/hooks/usePriceBenchmarkSuggestions';
import ChangePrice from '../change-price';
import EffectivenessIndicator from '../effectiveness-indicator';
import Label from '../label';
import Price from '../price';
import {
	LABELS,
	LABEL_CHANGE_EFFECTIVENESS,
	LABEL_AVG_PRICE_ON_GOOGLE,
	LABEL_PRICE_GAP_PERCENT,
	LABEL_SUGGESTED_PRICE,
	LABEL_REGULAR_PRICE,
	LABEL_ACTION,
} from '../constants';
import './index.scss';
import Wrapper from '../empty-metrics-notice/wrapper';

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
		id: 'title',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: true,
		label: __( 'Product', 'google-listings-and-ads' ),
		getValue: ( { item } ) => {
			return item?.product?.title || null;
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
		id: 'regular_price',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: false,
		label: <Label labelKey={ LABEL_REGULAR_PRICE } />,
		render: ( { item } ) => {
			return <Price amount={ item.regular_price } highlight />;
		},
	},
	{
		id: 'price_on_google',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: false,
		header: <Label labelKey={ LABEL_AVG_PRICE_ON_GOOGLE } />,
		label: LABELS[ LABEL_AVG_PRICE_ON_GOOGLE ].title,
		render: ( { item } ) => {
			return <Price amount={ item.price_on_google } />;
		},
	},
	{
		id: 'price_gap',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: false,
		header: <Label labelKey={ LABEL_PRICE_GAP_PERCENT } />,
		label: LABELS[ LABEL_PRICE_GAP_PERCENT ].title,
		render: ( { item } ) => {
			if ( ! item.price_gap ) {
				return null;
			}

			return `${ item.price_gap }%`;
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

/**
 * PriceBenchmarkSuggestions component.
 *
 * This component fetches and displays price benchmark suggestions using the
 * `usePriceBenchmarkSuggestions` hook. It renders a table with the suggestions
 * data and handles responsiveness by providing different field configurations
 * for desktop and mobile views.
 *
 * @param {Object} props - The component props.
 * @param {boolean} props.isViewportMobile - Indicates if the viewport is in mobile size.
 * @param {boolean} props.isDataViewReady - Indicates if the DataView component is ready.
 * @return {JSX.Element} A div containing the DataViews component.
 */
const PriceBenchmarkSuggestions = ( { isViewportMobile, isDataViewReady } ) => {
	const { suggestions, hasFinishedResolution } =
		usePriceBenchmarkSuggestions();

	const { DataViews, filterSortAndPaginate } = wp.dataviews || {};

	const [ view, setView ] = useState( {
		type: 'table',
		search: '',
		page: 1,
		perPage: 10,
		layout: {},
		filters: [],
		fields: [],
		titleField: 'title',
		descriptionField: 'description',
		mediaField: 'image',
	} );

	const { data: shownData, paginationInfo } = useMemo( () => {
		const updatedData = isDataViewReady
			? filterSortAndPaginate( suggestions, view, [
					...PRODUCT_TABLE_FIELDS,
					...METRICS_TABLE_FIELDS,
			  ] )
			: {};
		return updatedData;
	}, [ isDataViewReady, filterSortAndPaginate, suggestions, view ] );

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

	const placeholderTableHeaders = useMemo( () => {
		return METRICS_TABLE_FIELDS.map( ( { id, label } ) => {
			return {
				key: id,
				label,
			};
		} );
	}, [] );

	useEffect( () => {
		setView( ( prevView ) => ( {
			...prevView,
			fields: viewportFields,
		} ) );
	}, [ viewportFields ] );

	if ( isDataViewReady === false && hasFinishedResolution ) {
		return (
			<Wrapper>
				<p className="gla-price-benchmark__error-message">
					{ __(
						'There was an error loading the price benchmark suggestions.',
						'google-listings-and-ads'
					) }
				</p>
			</Wrapper>
		);
	}

	if ( hasFinishedResolution && suggestions.length === 0 ) {
		return <EmptyMetricsNotice />;
	}

	return (
		<div className="gla-price-benchmark-suggestions">
			{ ! hasFinishedResolution && isDataViewReady && (
				<TablePlaceholder
					headers={ [
						{
							key: 'product',
							label: __( 'Product', 'google-listings-and-ads' ),
						},
						...placeholderTableHeaders,
					] }
					caption={ __( 'Loading data…', 'google-listings-and-ads' ) }
					numberOfRows={ 10 }
				/>
			) }

			{ hasFinishedResolution && isDataViewReady && (
				<DataViews
					getItemId={ ( item ) => item?.product?.id }
					fields={ [
						...PRODUCT_TABLE_FIELDS,
						...METRICS_TABLE_FIELDS,
					] }
					data={ shownData }
					view={ view }
					paginationInfo={ paginationInfo }
					onChangeView={ handleOnChangeView }
					defaultLayouts={ [] }
				/>
			) }
		</div>
	);
};

export default withViewportMatch( {
	isViewportMobile: '< medium',
} )( PriceBenchmarkSuggestions );
