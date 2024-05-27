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
import RebrandingTour from ".~/components/tours/rebranding-tour";
import './index.scss';

/**
 * Renders the Attribute Mapping Page
 *
 * @return {JSX.Element} The component markup
 */
const AttributeMapping = () => {
	return (
		<div className="gla-attribute-mapping">
			<NavigationClassic />
			<RebrandingTour />
			<Section
				title={ __( 'Manage attributes', 'google-listings-and-ads' ) }
				description={ <AttributeMappingDescription /> }
			>
				<AttributeMappingTable />
			</Section>
		</div>
	);
};

export default AttributeMapping;
