/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Table } from '@woocommerce/components';
import { CardBody, CardFooter, Flex } from '@wordpress/components';
import GridIconTrash from 'gridicons/dist/trash';
/**
 * Internal dependencies
 */
import Card from '.~/wcdl/section/card';
import AppButton from '.~/components/app-button';
import AppTableCardDiv from '.~/components/app-table-card-div';
import AppIconButton from '.~/components/app-icon-button';
import AttributeMappingTableCategories from './attribute-mapping-table-categories';

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

const parseCategories = ( condition, categories ) => {
	if ( condition === 'ALL' ) {
		return __( 'All', 'google-listings-and-ads' );
	}

	const conversionMap = {
		categories: (
			<AttributeMappingTableCategories
				categories={ categories.split( ',' ) }
			/>
		),
	};

	if ( condition === 'ONLY' ) {
		return createInterpolateElement(
			__( 'Only in <categories />', 'google-listings-and-ads' ),
			conversionMap
		);
	}

	return createInterpolateElement(
		__( 'All except <categories />', 'google-listings-and-ads' ),
		conversionMap
	);
};

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
										{ parseCategories(
											rule.category_conditional_type,
											rule.categories
										) }
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
										<AppIconButton
											icon={
												<GridIconTrash size={ 18 } />
											}
										/>
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
