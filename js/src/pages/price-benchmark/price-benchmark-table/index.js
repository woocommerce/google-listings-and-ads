/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { withViewportMatch } from '@wordpress/viewport';
import { TablePlaceholder } from '@woocommerce/components';
import { useState, useMemo, useEffect, useCallback } from '@wordpress/element';
import { DataViews, filterSortAndPaginate } from '@wordpress/dataviews/wp';

/**
 * Internal dependencies
 */
import './index.scss';

// Defines the base fields (image, title, description) that are consistently used across all tables.
const BASE_FIELDS = [
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
					className="gla-price-benchmark-table__image"
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

/**
 * PriceBenchmarkTable component renders a table for price benchmarking.
 *
 * @param {Object} props - The component props.
 * @param {Array} [props.data=[]] - The data to be displayed in the table.
 * @param {Array} props.fields - The fields to be displayed in the table for desktop view.
 * @param {Array} props.fieldsMobile - The fields to be displayed in the table for mobile view.
 * @param {boolean} props.isViewportMobile - Indicates if the viewport is in mobile size.
 * @param {boolean} props.isReady - Indicates if the data is ready to be displayed.
 *
 * @return {JSX.Element} The rendered PriceBenchmarkTable component.
 */
const PriceBenchmarkTable = ( {
	data = [],
	fields,
	fieldsMobile,
	isViewportMobile,
	isReady,
} ) => {
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
		const updatedData = filterSortAndPaginate( data, view, [
			...BASE_FIELDS,
			...fields,
		] );
		return updatedData;
	}, [ view, data, fields ] );

	const handleOnChangeView = useCallback( ( newView ) => {
		setView( newView );
	}, [] );

	// Determine the fields to be displayed based on the viewport size.
	const viewportFields = useMemo( () => {
		if ( isViewportMobile ) {
			return fieldsMobile;
		}
		return fields.map( ( field ) => field.id );
	}, [ isViewportMobile, fields, fieldsMobile ] );

	const placeholderTableHeaders = useMemo( () => {
		return fields.map( ( { id, label } ) => {
			return {
				key: id,
				label,
			};
		} );
	}, [ fields ] );

	useEffect( () => {
		setView( ( prevView ) => ( {
			...prevView,
			fields: viewportFields,
		} ) );
	}, [ viewportFields ] );

	return (
		<div className="gla-price-benchmark-table">
			{ ! isReady && (
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

			{ isReady && (
				<DataViews
					getItemId={ ( item ) => item?.product?.id }
					fields={ [ ...BASE_FIELDS, ...fields ] }
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
} )( PriceBenchmarkTable );
