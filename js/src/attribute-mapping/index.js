/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AttributeMappingDescription from './attribute-mapping-description';
import AttributeMappingTable from './attribute-mapping-table';
import NavigationClassic from '.~/components/navigation-classic';
import './index.scss';

const DUMMY_TABLE_DATA = [
	{
		destination: 'adult',
		source: 'yes',
		source_name: 'Yes',
		category_conditional_type: 'ALL',
	},
	{
		destination: 'brands',
		source: 'taxonomy:product_brands',
		source_name: 'Taxonomy - Product Brands',
		category_conditional_type: 'EXCEPT',
		categories: '1,2',
	},
	{
		destination: 'color',
		source: 'attribute:color',
		source_name: 'Attribute - Color',
		category_conditional_type: 'ONLY',
		categories:
			'1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3',
	},
];

/**
 * Renders the Attribute Mapping Page
 *
 * @return {JSX.Element} The component markup
 */
const AttributeMapping = () => {
	return (
		<div className="gla-settings-attribute-mapping">
			<NavigationClassic />
			<Section
				title={ __( 'Attribute Mapping', 'google-listings-and-ads' ) }
				description={ <AttributeMappingDescription /> }
			>
				<AttributeMappingTable rules={ DUMMY_TABLE_DATA } />
			</Section>
		</div>
	);
};

export default AttributeMapping;
