/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import DismissibleNotice from '.~/components/dismissible-notice';
import AttributeMappingDescription from './attribute-mapping-description';
import AttributeMappingTable from './attribute-mapping-table';
import './index.scss';
import NavigationClassic from '.~/components/navigation-classic';

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
				<VerticalGapLayout size="overlap">
					<DismissibleNotice className="gla-settings-attribute-mapping__notice">
						{ createInterpolateElement(
							__(
								'After a review of your products, we’ve pre-configured a select number of attributes for you. Click the ellipsis icon (•••) and select <strong>Edit</strong> to assign a reference field or a default value to the target attribute.',
								'google-listings-and-ads'
							),
							{
								strong: <strong></strong>,
							}
						) }
					</DismissibleNotice>
					<AttributeMappingTable />
				</VerticalGapLayout>
			</Section>
		</div>
	);
};

export default AttributeMapping;
