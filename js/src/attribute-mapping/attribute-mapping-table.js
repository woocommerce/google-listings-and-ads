/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Table } from '@woocommerce/components';
import { CardBody, CardFooter } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Card from '.~/wcdl/section/card';
import AppButton from '.~/components/app-button';
import AppTableCardDiv from '.~/components/app-table-card-div';

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

/**
 * Renders the Attribute Mapping table component
 *
 * @return {JSX.Element} The component
 */
const AttributeMappingTable = () => {
	return (
		<AppTableCardDiv>
			<Card>
				<CardBody size={ null }>
					<Table
						caption={ __(
							'Attribute Mapping configuration',
							'google-listings-and-ads'
						) }
						headers={ ATTRIBUTE_MAPPING_TABLE_HEADERS }
						numberOfRows={ 1 }
						rows={ [] } // TODO: Implement data getter
					/>
				</CardBody>
				<CardFooter
					align="start"
					className="gla-attribute-mapping__table-footer"
				>
					<AppButton
						isSecondary
						onClick={ () => {} } // TODO: Implement button logic
						text={ __(
							'Add new attribute mapping',
							'google-listings-and-ads'
						) }
					/>
				</CardFooter>
			</Card>
		</AppTableCardDiv>
	);
};

export default AttributeMappingTable;
