/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import AppDocumentationLink from '~/components/app-documentation-link';
import ShippingTimeSetup from './shipping-time-setup';

/**
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-shipping', link_id: 'shipping-read-more', href: 'https://support.google.com/merchants/answer/7050921' }`
 */

const ShippingTimeSection = () => {
	return (
		<Section
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
							href="https://support.google.com/merchants/answer/7050921"
							linkId="shipping-read-more"
						>
							{ __( 'Read more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</div>
			}
			title={ __( 'Shipping times', 'google-listings-and-ads' ) }
		>
			<ShippingTimeSetup />
		</Section>
	);
};

export default ShippingTimeSection;
