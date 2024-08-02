/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getEditPhoneNumberUrl, getEditStoreAddressUrl } from '.~/utils/urls';
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import useGoogleMCPhoneNumber from '.~/hooks/useGoogleMCPhoneNumber';
import Section from '.~/wcdl/section';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AppDocumentationLink from '.~/components/app-documentation-link';
import PhoneNumberCard, { PhoneNumberCardPreview } from './phone-number-card';
import StoreAddressCard, {
	StoreAddressCardPreview,
} from './store-address-card';
import usePhoneNumberCheckTrackEventEffect from './usePhoneNumberCheckTrackEventEffect';

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

const mcTitle = __( 'Verify contact information', 'google-listings-and-ads' );
const settingsTitle = __( 'Contact information', 'google-listings-and-ads' );

/**
 * Renders a preview of contact information section,
 * or a <NoContactInformationCard> if contact informations are not saved yet.
 */
export function ContactInformationPreview() {
	return (
		<Section title={ settingsTitle } description={ description }>
			<VerticalGapLayout size="overlap">
				<PhoneNumberCardPreview
					editHref={ getEditPhoneNumberUrl() }
					learnMore={
						<AppDocumentationLink
							context="settings-no-phone-number-notice"
							linkId={ learnMoreLinkId }
							href={ learnMoreUrl }
						>
							{ __( 'Learn more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					}
				/>
				<StoreAddressCardPreview
					editHref={ getEditStoreAddressUrl() }
					learnMore={
						<AppDocumentationLink
							context="settings-no-store-address-notice"
							linkId={ learnMoreLinkId }
							href={ learnMoreUrl }
						>
							{ __( 'Learn more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					}
				/>
			</VerticalGapLayout>
		</Section>
	);
}

/**
 * Renders a contact information section with specified initial state and texts.
 *
 * @param {Object} props React props.
 * @param {Function} [props.onPhoneNumberVerified] Called when the phone number is verified or has been verified.
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-contact-information', link_id: 'contact-information-read-more', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information' }`
 * @fires gla_documentation_link_click with `{ context: 'settings-no-phone-number-notice', link_id: 'contact-information-read-more', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information' }`
 * @fires gla_documentation_link_click with `{ context: 'settings-no-store-address-notice', link_id: 'contact-information-read-more', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information' }`
 */
const ContactInformation = ( { onPhoneNumberVerified } ) => {
	const { adapter } = useAdaptiveFormContext();
	const phone = useGoogleMCPhoneNumber();
	const title = mcTitle;
	const trackContext = 'setup-mc-contact-information';

	usePhoneNumberCheckTrackEventEffect( phone );

	return (
		<Section
			title={ title }
			description={
				<div>
					{ description }
					<p>
						<AppDocumentationLink
							context={ trackContext }
							linkId={ learnMoreLinkId }
							href={ learnMoreUrl }
						>
							{ __( 'Learn more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</div>
			}
		>
			<VerticalGapLayout size="large">
				<PhoneNumberCard
					view="setup-mc"
					phoneNumber={ phone }
					showValidation={ adapter.requestedShowValidation }
					onPhoneNumberVerified={ onPhoneNumberVerified }
				/>
				<StoreAddressCard
					showValidation={ adapter.requestedShowValidation }
				/>
			</VerticalGapLayout>
		</Section>
	);
};

export default ContactInformation;
