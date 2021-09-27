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

const description = __(
	'Your contact information is required by Google for verification purposes. It will be shared with the Google Merchant Center and will not be displayed to customers.',
	'google-listings-and-ads'
);

const mcTitle = __( 'Enter contact information', 'google-listings-and-ads' );
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
 * Renders a contact information section with specified initial state and texts, determined by the `view` prop.
 *
 * @param {Object} props React props.
 * @param {'setup-mc'|'settings'} props.view Indicate where this component is used.
 * @param {Function} [props.onPhoneNumberVerified] Called when the phone number is verified.
 */
export default function ContactInformation( { view, onPhoneNumberVerified } ) {
	const phone = useGoogleMCPhoneNumber();
	const isSetupMC = view === 'setup-mc';

	/**
	 * Since it still lacking the phone verification state,
	 * all onboarding accounts are considered unverified phone numbers.
	 *
	 * TODO: replace the code at next line back to the original logic with
	 * `const initEditing = isSetupMC ? null : true;`
	 * after the phone verification state can be detected.
	 */
	const initEditing = true;

	const title = isSetupMC ? mcTitle : settingsTitle;
	const trackContext = isSetupMC
		? 'setup-mc-contact-information'
		: 'settings-contact-information';

	usePhoneNumberCheckTrackEventEffect( phone );

	return (
		<Section
			title={ title }
			description={
				<div>
					<p>{ description }</p>
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
					view={ view }
					phoneNumber={ phone }
					initEditing={ initEditing }
					onPhoneNumberVerified={ onPhoneNumberVerified }
				/>
				<StoreAddressCard />
			</VerticalGapLayout>
		</Section>
	);
}
