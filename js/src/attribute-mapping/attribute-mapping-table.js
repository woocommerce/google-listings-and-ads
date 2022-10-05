/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Pagination, Table, TablePlaceholder } from '@woocommerce/components';
import { CardBody, CardFooter, Flex, FlexItem } from '@wordpress/components';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Card from '.~/wcdl/section/card';
import AppButton from '.~/components/app-button';
import AppTableCardDiv from '.~/components/app-table-card-div';
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import AttributeMappingTableCategories from './attribute-mapping-table-categories';
import AttributeMappingRuleModal from './attribute-mapping-rule-modal';
import AttributeMappingDeleteRuleModal from './attribute-mapping-delete-rule-modal';
import useMappingAttributes from '.~/hooks/useMappingAttributes';
import useMappingRules from '.~/hooks/useMappingRules';
import usePagination from '.~/hooks/usePagination';
import { recordTablePageEvent } from '.~/utils/recordEvent';

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
	const { page, setPage } = usePagination( 'attribute-mapping' );

	const {
		data: { rules, total },
		hasFinishedResolution: rulesHasFinishedResolution,
	} = useMappingRules( { page } );

	const {
		data: attributes,
		hasFinishedResolution: attributesHasFinishedResolution,
	} = useMappingAttributes();

	const parseDestinationName = ( destination ) =>
		attributes.find( ( e ) => e.id === destination )?.label || '';

	const isLoading =
		! attributesHasFinishedResolution || ! rulesHasFinishedResolution;

	const handlePageChange = ( newPage, direction ) => {
		setPage( newPage );
		recordTablePageEvent( `attribute-mapping-rules`, newPage, direction );
	};

	/**
	 * Prevent to stay in a page without rules.
	 * This is because maybe the user is in the page 2 which has only one rule.
	 * If the user deletes that rule we don't want to stay in page 2 anymore, since it doesn't exists.
	 */
	useEffect( () => {
		if ( rulesHasFinishedResolution && rules?.length === 0 && page > 1 ) {
			setPage( page - 1 );
		}
	}, [ page, rules, rulesHasFinishedResolution ] );

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
					align="between"
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
					<Pagination
						page={ page }
						perPage={ 10 }
						total={ total }
						showPagePicker={ false }
						showPerPagePicker={ false }
						onPageChange={ handlePageChange }
					/>
				</CardFooter>
			</Card>
		</AppTableCardDiv>
	);
};

export default AttributeMappingTable;
