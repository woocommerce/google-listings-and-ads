/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Table } from '@woocommerce/components';
import { CardBody, CardFooter, Flex } from '@wordpress/components';
import GridiconTrash from 'gridicons/dist/trash';
/**
 * Internal dependencies
 */
import Card from '.~/wcdl/section/card';
import AppButton from '.~/components/app-button';
import AppTableCardDiv from '.~/components/app-table-card-div';
import AttributeMappingTableCategories from './attribute-mapping-table-categories';
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import AttributeMappingRuleModal from '.~/attribute-mapping/attribute-mapping-rule-modal';

const ATTRIBUTE_MAPPING_TABLE_HEADERS = [
	{
		key: 'attribute',
		label: __( 'Target Attribute', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'source',
		label: __( 'Data Source / Default Value', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'categories',
		label: __( 'Categories', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'controls',
		label: '',
		required: true,
	},
];

const DUMMY_DESTINATIONS = [
	{ id: 'adult', name: 'Adult' },
	{ id: 'brands', name: 'Brands' },
	{ id: 'color', name: 'Color' },
];

const parseDestinationName = ( destination ) =>
	DUMMY_DESTINATIONS.find( ( e ) => e.id === destination ).name;

/**
 * Renders the Attribute Mapping table component
 *
 * @param {Object} props The component props
 * @param {Object} props.rules The rules to show in the table
 * @return {JSX.Element} The component
 */
const AttributeMappingTable = ( { rules } ) => {
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
						rows={ rules.map( ( rule ) => [
							{
								display: parseDestinationName(
									rule.destination
								),
							},
							{
								display: (
									<span className="gla-attribute-mapping__table-label">
										{ rule.source_name }
									</span>
								),
							},
							{
								display: (
									<span className="gla-attribute-mapping__table-categories">
										<AttributeMappingTableCategories
											categories={ rule.categories }
											condition={
												rule.category_conditional_type
											}
										/>
									</span>
								),
							},
							{
								display: (
									<Flex justify="end">
										<AppButton isLink>
											{ __(
												'Manage',
												'google-listings-and-ads'
											) }
										</AppButton>
										<AppButton icon={ <GridiconTrash /> } />
									</Flex>
								),
							},
						] ) }
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
