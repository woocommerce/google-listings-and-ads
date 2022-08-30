/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { EmptyTable, Table } from '@woocommerce/components';

const ATTRIBUTE_MAPPING_TABLE_HEADERS = [
	{
		key: 'destination',
		label: __( 'Target Attribute', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'source',
		label: __(
			'Reference Field / Default Value',
			'google-listings-and-ads'
		),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'categories',
		label: __( 'Categories', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
];

const AttributeMappingTable = () => {
	return (
		<Table
			headers={ ATTRIBUTE_MAPPING_TABLE_HEADERS }
			numberOfRows={ 1 }
			rows={ [] }
		/>
	);
};

export default AttributeMappingTable;
