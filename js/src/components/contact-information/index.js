/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getEditPhoneNumberUrl, getEditStoreAddressUrl } from '.~/utils/urls';
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
	'https://docs.woocommerce.com/document/google-listings-and-ads/#contact-information';

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
 * @param {Function} [props.onPhoneNumberVerified] Called when the phone number is verified.
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-contact-information', link_id: 'contact-information-read-more', href: 'https://docs.woocommerce.com/document/google-listings-and-ads/#contact-information' }`
 * @fires gla_documentation_link_click with `{ context: 'settings-no-phone-number-notice', link_id: 'contact-information-read-more', href: 'https://docs.woocommerce.com/document/google-listings-and-ads/#contact-information' }`
 * @fires gla_documentation_link_click with `{ context: 'settings-no-store-address-notice', link_id: 'contact-information-read-more', href: 'https://docs.woocommerce.com/document/google-listings-and-ads/#contact-information' }`
 */
const ContactInformation = ( { onPhoneNumberVerified } ) => {
	const phone = useGoogleMCPhoneNumber();

	/**
	 * Since it is still lacking the phone verification state,
	 * all onboarding accounts are considered unverified phone numbers.
	 *
	 * TODO: replace the code at next line back to the original logic with
	 * `const initEditing = null;`
	 * after the phone verification state can be detected.
	 */
	const initEditing = true;

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
					initEditing={ initEditing }
					onPhoneNumberVerified={ onPhoneNumberVerified }
				/>
				<StoreAddressCard />
			</VerticalGapLayout>
		</Section>
	);
};

export default ContactInformation;
