/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CompareTableCard from '../compare-table-card';

const compareBy = 'products';
const compareParam = 'filter';

/**
 * All products table, with compare feature.
 *
 * @see AllProgramsTableCard
 * @see CompareTableCard
 *
 * @param {Object} props React props.
 * @param {Array<Metric>} props.metrics Metrics to display.
 * @param {boolean} props.isLoading Whether the data is still being loaded.
 * @param {Array<ProductsData>} props.products Report's products data.
 * @param {Object} [props.restProps] Properties to be forwarded to CompareTableCard.
 */
const CompareProductsTableCard = ( {
	metrics,
	isLoading,
	products,
	...restProps
} ) => {
	return (
		<CompareTableCard
			title={ __( 'Products', 'google-listings-and-ads' ) }
			compareButonTitle={ __(
				'Select one or more products to compare',
				'google-listings-and-ads'
			) }
			nameHeader={ __( 'Product title', 'google-listings-and-ads' ) }
			nameCell={ ( row ) => row.name || row.title }
			compareBy={ compareBy }
			compareParam={ compareParam }
			metrics={ metrics }
			isLoading={ isLoading }
			data={ products }
			{ ...restProps }
		/>
	);
};

export default CompareProductsTableCard;

/**
 * @typedef {import("../index.js").Metric} Metric
 * @typedef {import("../index.js").ProductsData} ProductsData
 */
