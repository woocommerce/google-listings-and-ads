/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppDocumentationLink from '.~/components/app-documentation-link';
import ShippingTimeSetup from './shipping-time/shipping-time-setup';

const ShippingTimeSection = ( { formProps } ) => {
	return (
		<Section
			title={ __( 'Shipping times', 'google-listings-and-ads' ) }
			description={
				<div>
					<p>
						{ __(
							'Your shipping times will be shown to potential customers on Google.',
							'google-listings-and-ads'
						) }
					</p>
					<p>
						<AppDocumentationLink
							context="setup-mc-shipping"
							linkId="shipping-read-more"
							href="https://support.google.com/merchants/answer/7050921"
						>
							{ __( 'Read more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</div>
			}
		>
			<Section.Card>
				<Section.Card.Body>
					<Section.Card.Title>
						{ __(
							'Estimated shipping times',
							'google-listings-and-ads'
						) }
					</Section.Card.Title>
					<ShippingTimeSetup formProps={ formProps } />
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default ShippingTimeSection;
