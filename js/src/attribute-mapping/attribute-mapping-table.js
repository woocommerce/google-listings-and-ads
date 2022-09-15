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
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import AttributeMappingRuleModal from '.~/attribute-mapping/attribute-mapping-rule-modal';

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
					<AppButtonModalTrigger
						button={
							<AppButton
								isSecondary
								text={ __(
									'Create attribute rule',
									'google-listings-and-ads'
								) }
								eventName="gla_attribute_mapping_new_rule_click"
								eventProps={ {
									context: 'attribute-mapping-table',
								} }
							/>
						}
						modal={ <AttributeMappingRuleModal /> }
					/>
				</CardFooter>
			</Card>
		</AppTableCardDiv>
	);
};

export default AttributeMappingTable;
