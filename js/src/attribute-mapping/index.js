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
import AttributeMappingFeatureTour from './attribute-mapping-feature-tour';
import NavigationClassic from '.~/components/navigation-classic';
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
			<Section
				title={ __( 'Manage attributes', 'google-listings-and-ads' ) }
				description={ <AttributeMappingDescription /> }
			>
				<AttributeMappingTable />
				<AttributeMappingFeatureTour />
			</Section>
		</div>
	);
};

export default AttributeMapping;
