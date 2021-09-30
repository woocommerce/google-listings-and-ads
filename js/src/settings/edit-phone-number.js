/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getSettingsUrl } from '.~/utils/urls';
import FullContainer from '.~/components/full-container';
import TopBar from '.~/components/stepper/top-bar';
import HelpIconButton from '.~/components/help-icon-button';
import useGoogleMCPhoneNumber from '.~/hooks/useGoogleMCPhoneNumber';
import Section from '.~/wcdl/section';
import AppDocumentationLink from '.~/components/app-documentation-link';
import PhoneNumberCard from '.~/components/contact-information/phone-number-card';
import usePhoneNumberCheckTrackEventEffect from '.~/components/contact-information/usePhoneNumberCheckTrackEventEffect';

const learnMoreLinkId = 'contact-information-read-more';
const learnMoreUrl =
	'https://docs.woocommerce.com/document/google-listings-and-ads/#contact-information';

/**
 * Renders the phone number settings page.
 *
 * @see PhoneNumberCard
 */
export default function EditPhoneNumber() {
	const phone = useGoogleMCPhoneNumber();

	usePhoneNumberCheckTrackEventEffect( phone );

	return (
		<FullContainer>
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
		</FullContainer>
	);
}
