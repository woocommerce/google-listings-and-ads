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
import Section from '~/components/section';
import AppButton from '~/components/app-button';
import AppTableCardDiv from '~/components/app-table-card-div';
import AppButtonModalTrigger from '~/components/app-button-modal-trigger';
import AttributeMappingTableCategories from './attribute-mapping-table-categories';
import AttributeMappingRuleModal from './attribute-mapping-rule-modal';
import AttributeMappingDeleteRuleModal from './attribute-mapping-delete-rule-modal';
import AttributeMappingSync from './attribute-mapping-sync';
import useMappingAttributes from '~/hooks/useMappingAttributes';
import useMappingRules from '~/hooks/useMappingRules';
import usePagination from '~/hooks/usePagination';
import { recordGlaEvent, recordTablePageEvent } from '~/utils/tracks';

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
 * @fires gla_modal_open When any of the modals is open with `{ context: 'attribute-mapping-manage-rule-modal' | 'attribute-mapping-create-rule-modal' }`
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
			<Section.Card className="gla-attribute-mapping__card">
				<CardBody size={ null }>
					{ isLoading ? (
						<TablePlaceholder
							caption={ __(
								'Loading Attribute Mapping rules',
								'google-listings-and-ads'
							) }
							headers={ ATTRIBUTE_MAPPING_TABLE_HEADERS }
						/>
					) : (
						<Table
							caption={ __(
								'Attribute Mapping configuration',
								'google-listings-and-ads'
							) }
							emptyMessage={ __(
								'You have no attribute rules',
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
															eventName="gla_modal_open"
															eventProps={ {
																context:
																	'attribute-mapping-manage-rule-modal',
															} }
															text={ __(
																'Edit',
																'google-listings-and-ads'
															) }
															isLink
														/>
													}
													modal={
														<AttributeMappingRuleModal
															onRequestClose={ (
																action
															) => {
																recordGlaEvent(
																	'gla_modal_closed',
																	{
																		context:
																			'attribute-mapping-manage-rule-modal',
																		action,
																	}
																);
															} }
															rule={ rule }
														/>
													}
												/>
											</FlexItem>
											<FlexItem>
												<AppButtonModalTrigger
													button={
														<AppButton
															eventName="gla_modal_open"
															eventProps={ {
																context:
																	'attribute-mapping-delete-rule-modal',
															} }
															text={ __(
																'Delete',
																'google-listings-and-ads'
															) }
															isLink
														/>
													}
													modal={
														<AttributeMappingDeleteRuleModal
															onRequestClose={ (
																action
															) => {
																recordGlaEvent(
																	'gla_modal_closed',
																	{
																		context:
																			'attribute-mapping-delete-rule-modal',
																		action,
																	}
																);
															} }
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
								eventName="gla_modal_open"
								eventProps={ {
									context:
										'attribute-mapping-create-rule-modal',
								} }
								text={ __(
									'Create attribute rule',
									'google-listings-and-ads'
								) }
								isSecondary
							/>
						}
						modal={
							<AttributeMappingRuleModal
								onRequestClose={ ( action ) => {
									recordGlaEvent( 'gla_modal_closed', {
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
						onPageChange={ handlePageChange }
						page={ page }
						perPage={ PER_PAGE }
						showPagePicker={ false }
						showPerPagePicker={ false }
						total={ total }
					/>
					<AttributeMappingSync />
				</CardFooter>
			</Section.Card>
		</AppTableCardDiv>
	);
};

export default AttributeMappingTable;
