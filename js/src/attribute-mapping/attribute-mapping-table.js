/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Pagination, Table, TablePlaceholder } from '@woocommerce/components';
import { CardBody, CardFooter, Flex, FlexItem } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

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
import AttributeMappingSync from './attribute-mapping-sync';
import useMappingAttributes from '.~/hooks/useMappingAttributes';
import useMappingRules from '.~/hooks/useMappingRules';
import usePagination from '.~/hooks/usePagination';
import { recordTablePageEvent } from '.~/utils/recordEvent';

const PER_PAGE = 10;
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
 * @fires gla_modal_closed When any of the modals is closed
 * @fires gla_modal_open When any of the modals is open with `context: attribute-mapping-manage-rule-modal`
 * @return {JSX.Element} The component
 */
const AttributeMappingTable = () => {
	const { page, setPage } = usePagination( 'attribute-mapping' );

	const {
		data: { rules, total },
		hasFinishedResolution: rulesHasFinishedResolution,
	} = useMappingRules( { page, perPage: PER_PAGE } );

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
	}, [ page, rules, rulesHasFinishedResolution, setPage ] );

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
															eventName="gla_modal_open"
															eventProps={ {
																context:
																	'attribute-mapping-manage-rule-modal',
															} }
														/>
													}
													modal={
														<AttributeMappingRuleModal
															rule={ rule }
															onRequestClose={ (
																action
															) => {
																recordEvent(
																	'gla_modal_closed',
																	{
																		context:
																			'attribute-mapping-manage-rule-modal',
																		action,
																	}
																);
															} }
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
															eventName="gla_modal_open"
															eventProps={ {
																context:
																	'attribute-mapping-delete-rule-modal',
															} }
														/>
													}
													modal={
														<AttributeMappingDeleteRuleModal
															rule={ rule }
															onRequestClose={ (
																action
															) => {
																recordEvent(
																	'gla_modal_closed',
																	{
																		context:
																			'attribute-mapping-delete-rule-modal',
																		action,
																	}
																);
															} }
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
								eventName="gla_modal_open"
								eventProps={ {
									context:
										'attribute-mapping-create-rule-modal',
								} }
							/>
						}
						modal={
							<AttributeMappingRuleModal
								onRequestClose={ ( action ) => {
									recordEvent( 'gla_modal_closed', {
										context:
											'attribute-mapping-create-rule-modal',
										action,
									} );
								} }
							/>
						}
					/>
					<Pagination
						className="gla-attribute-mapping__pagination"
						page={ page }
						perPage={ PER_PAGE }
						total={ total }
						showPagePicker={ false }
						showPerPagePicker={ false }
						onPageChange={ handlePageChange }
					/>
					<AttributeMappingSync />
				</CardFooter>
			</Card>
		</AppTableCardDiv>
	);
};

export default AttributeMappingTable;
