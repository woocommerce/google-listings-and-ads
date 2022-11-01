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
import AttributeMappingTour from '.~/attribute-mapping/attribute-mapping-tour';

/**
 * Renders the Attribute Mapping Page
 *
 * @return {JSX.Element} The component markup
 */
const AttributeMapping = () => {
	return (
		<div className="gla-attribute-mapping">
			<NavigationClassic />
			<Section
				title={ __( 'Attribute Mapping', 'google-listings-and-ads' ) }
				description={ <AttributeMappingDescription /> }
			>
				<AttributeMappingTable />
			</Section>
			<AttributeMappingTour />
		</div>
	);
};

export default AttributeMapping;
