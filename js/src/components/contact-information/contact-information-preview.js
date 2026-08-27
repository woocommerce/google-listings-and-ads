/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getEditStoreAddressUrl } from '~/utils/urls';
import Section from '~/components/section';
import AppDocumentationLink from '~/components/app-documentation-link';
import { StoreAddressCardPreview } from './store-address-card';

const learnMoreLinkId = 'contact-information-read-more';
const learnMoreUrl =
	'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information';

const description = (
	<>
		<p>
			{ __(
				'Your contact information is required for verification by Google.',
				'google-listings-and-ads'
			) }
		</p>
		<p>
			{ __(
				'It would be shared with Google Merchant Center for store verification and would not be displayed to customers.',
				'google-listings-and-ads'
			) }
		</p>
	</>
);

const settingsTitle = __( 'Contact information', 'google-listings-and-ads' );

/**
 * Renders a preview of contact information section,
 * or a notice if contact information is outdated.
 */
export default function ContactInformationPreview() {
	return (
		<Section description={ description } title={ settingsTitle }>
			<StoreAddressCardPreview
				editHref={ getEditStoreAddressUrl() }
				learnMore={
					<AppDocumentationLink
						context="settings-no-store-address-notice"
						href={ learnMoreUrl }
						linkId={ learnMoreLinkId }
					>
						{ __( 'Learn more', 'google-listings-and-ads' ) }
					</AppDocumentationLink>
				}
			/>
		</Section>
	);
}
