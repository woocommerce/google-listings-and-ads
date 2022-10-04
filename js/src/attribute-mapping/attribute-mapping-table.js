/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Table, TablePlaceholder } from '@woocommerce/components';
import { CardBody, CardFooter, Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Card from '.~/wcdl/section/card';
import AppButton from '.~/components/app-button';
import AppTableCardDiv from '.~/components/app-table-card-div';
import AttributeMappingTableCategories from './attribute-mapping-table-categories';
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import AttributeMappingRuleModal from '.~/attribute-mapping/attribute-mapping-rule-modal';
import useMappingAttributes from '.~/hooks/useMappingAttributes';
import useMappingRules from '.~/hooks/useMappingRules';
import AttributeMappingDeleteRuleModal from '.~/attribute-mapping/attribute-mapping-delete-rule-modal';

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

/**
 * Renders the Attribute Mapping table component
 *
 * @return {JSX.Element} The component
 */
const AttributeMappingTable = () => {
	const {
		data: rules,
		hasFinishedResolution: rulesHasFinishedResolution,
	} = useMappingRules();

	const {
		data: attributes,
		hasFinishedResolution: attributesHasFinishedResolution,
	} = useMappingAttributes();

	const parseDestinationName = ( destination ) =>
		attributes.find( ( e ) => e.id === destination )?.label || '';

	const isLoading =
		! attributesHasFinishedResolution || ! rulesHasFinishedResolution;

	return (
		<AppTableCardDiv>
			<Card>
				<CardBody size={ null }>
					{ isLoading ? (
						<TablePlaceholder
							headers={ ATTRIBUTE_MAPPING_TABLE_HEADERS }
							caption={ __(
								'Loading Attribute Mapping rules',
								'google-listings-and-ads'
							) }
						/>
					) : (
						<Table
							caption={ __(
								'Attribute Mapping configuration',
								'google-listings-and-ads'
							) }
							headers={ ATTRIBUTE_MAPPING_TABLE_HEADERS }
							rows={ rules.map( ( rule ) => [
								{
									display: parseDestinationName(
										rule.attribute
									),
								},
								{
									// TODO: replace with source_name after implementation
									display: (
										<span className="gla-attribute-mapping__table-label">
											{ rule.source }
										</span>
									),
								},
								{
									display: (
										<span className="gla-attribute-mapping__table-categories">
											<AttributeMappingTableCategories
												categories={ rule.categories }
												condition={
													rule.category_condition_type
												}
											/>
										</span>
									),
								},
								{
									display: (
										<Flex justify="end">
											<FlexItem>
												<AppButtonModalTrigger
													button={
														<AppButton
															isLink
															text={ __(
																'Manage',
																'google-listings-and-ads'
															) }
															eventName="gla_attribute_mapping_new_rule_click"
															eventProps={ {
																context:
																	'attribute-mapping-table',
															} }
														/>
													}
													modal={
														<AttributeMappingRuleModal
															rule={ rule }
														/>
													}
												/>
											</FlexItem>
											<FlexItem>
												<AppButtonModalTrigger
													button={
														<AppButton
															isLink
															text={ __(
																'Delete',
																'google-listings-and-ads'
															) }
															eventName="gla_attribute_mapping_delete_rule_click"
															eventProps={ {
																context:
																	'attribute-mapping-table',
															} }
														/>
													}
													modal={
														<AttributeMappingDeleteRuleModal
															rule={ rule }
														/>
													}
												/>
											</FlexItem>
										</Flex>
									),
								},
							] ) }
						/>
					) }
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
