/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getSettingsUrl } from '.~/utils/urls';
import TopBar from '.~/components/stepper/top-bar';
import HelpIconButton from '.~/components/help-icon-button';
import useLayout from '.~/hooks/useLayout';
import useGoogleMCPhoneNumber from '.~/hooks/useGoogleMCPhoneNumber';
import Section from '.~/wcdl/section';
import AppDocumentationLink from '.~/components/app-documentation-link';
import PhoneNumberCard from '.~/components/contact-information/phone-number-card';
import usePhoneNumberCheckTrackEventEffect from '.~/components/contact-information/usePhoneNumberCheckTrackEventEffect';

const learnMoreLinkId = 'contact-information-read-more';
const learnMoreUrl =
	'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information';

/**
 * Renders the phone number settings page.
 *
 * @see PhoneNumberCard
 * @fires gla_documentation_link_click with `{ context: "settings-phone-number", link_id: "contact-information-read-more", href: "https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information" }`
 */
const EditPhoneNumber = () => {
	const phone = useGoogleMCPhoneNumber();

	usePhoneNumberCheckTrackEventEffect( phone );
	useLayout( 'full-content' );

	return (
		<>
			<TopBar
				title={ __( 'Edit phone number', 'google-listings-and-ads' ) }
				helpButton={
					<HelpIconButton eventContext="edit-phone-number" />
				}
				backHref={ getSettingsUrl() }
			/>
			<div className="gla-settings">
				<Section
					title={ __( 'Phone number', 'google-listings-and-ads' ) }
					description={
						<div>
							<p>
								{ __(
									'Your phone number is required by Google for verification purposes. It will be shared with the Google Merchant Center and will not be displayed to customers.',
									'google-listings-and-ads'
								) }
							</p>
							<p>
								<AppDocumentationLink
									context={ 'settings-phone-number' }
									linkId={ learnMoreLinkId }
									href={ learnMoreUrl }
								>
									{ __(
										'Learn more',
										'google-listings-and-ads'
									) }
								</AppDocumentationLink>
							</p>
						</div>
					}
				>
					<PhoneNumberCard
						view="settings"
						phoneNumber={ phone }
						initEditing={ true }
					/>
				</Section>
			</div>
		</>
	);
};

export default EditPhoneNumber;
