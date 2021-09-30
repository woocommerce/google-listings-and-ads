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
import ContactInformation from '.~/components/contact-information';

export default function EditPhoneNumber() {
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
				<ContactInformation view="settings" />
			</div>
		</FullContainer>
	);
}
