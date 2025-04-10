/**
 * External dependencies
 */
import { TablePlaceholder } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PriceBenchmarkTour from './price-benchmark-tour';

const TABLE_DATA_HEADERS = [
	{
		key: 'product',
		label: __( 'Product', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'effectiveness',
		label: __( 'Price Change Effectiveness', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'regular-price',
		label: __( 'Regular Price', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'avg-google-price',
		label: __( 'Avg. Price on Google', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'price-gap',
		label: __( 'Price Gap', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'suggested-price',
		label: __( 'Suggested Price', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'actions',
		label: __( 'Action', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
];

const PriceBenchmarkSuggestions = () => {
	return (
		<>
			<PriceBenchmarkTour />
			<TablePlaceholder
				headers={ TABLE_DATA_HEADERS }
				caption={ __(
					'Loading the product data…',
					'google-listings-and-ads'
				) }
			/>
		</>
	);
};

export default PriceBenchmarkSuggestions;
