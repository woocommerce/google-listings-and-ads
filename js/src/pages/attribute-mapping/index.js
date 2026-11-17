/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import AttributeMappingDescription from './attribute-mapping-description';
import AttributeMappingTable from './attribute-mapping-table';
import MainPageHeader from '~/components/main-page-header';
import GtinMigrationBanner from '~/components/gtin-migration-banner';
import ExperienceRatingBanner from '~/components/experience-rating-banner';
import './index.scss';

/**
 * Renders the Attribute Mapping Page
 *
 * @return {JSX.Element} The component markup
 */
const AttributeMapping = () => {
	return (
		<div className="gla-attribute-mapping">
			<MainPageHeader />
			<ExperienceRatingBanner />
			<GtinMigrationBanner />
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
