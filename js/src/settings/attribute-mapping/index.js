/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CardBody, CardFooter } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import SettingsHeader from '.~/settings/settings-header';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AttributeMappingTable from '.~/settings/attribute-mapping/attribute-mapping-table';
import AttributeMappingDescription from '.~/settings/attribute-mapping/attribute-mapping-description';
import AppTableCardDiv from '.~/components/app-table-card-div';
import Card from '.~/wcdl/section/card';
import AppButton from '.~/components/app-button';
import DismissibleNotice from '.~/components/dismissible-notice';
import './index.scss';
import { createInterpolateElement } from '@wordpress/element';

const AttributeMapping = () => {
	return (
		<div className="gla-settings-attribute-mapping">
			<SettingsHeader />
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
					<AppTableCardDiv>
						<Card>
							<CardBody size={ null }>
								<AttributeMappingTable />
							</CardBody>
							<CardFooter
								align="start"
								className="gla-settings-attribute-mapping__table-footer"
							>
								<AppButton
									className="gla-settings-attribute-mapping__button"
									isSecondary
									onClick={ () => {} }
									text={ __(
										'Add new attribute mapping',
										'google-listings-and-ads'
									) }
								/>
							</CardFooter>
						</Card>
					</AppTableCardDiv>
				</VerticalGapLayout>
			</Section>
		</div>
	);
};

export default AttributeMapping;
