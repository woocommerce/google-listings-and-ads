/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getSettingsUrl } from '.~/utils/urls';
import FullContainer from '.~/components/full-container';
import TopBar from '.~/components/stepper/top-bar';
import HelpIconButton from '.~/components/help-icon-button';
import Section from '.~/wcdl/section';
import AppButton from '.~/components/app-button';
import ContactInformation from '.~/components/contact-information';

export default function EditContactInformation() {
	const handlePhoneNumberChange = ( countryCallingCode, nationalNumber ) => {
		// TODO: [lite-contact-info] handle the onChange callback of phone number
		console.log( countryCallingCode, nationalNumber ); // eslint-disable-line
	};

	const handleSaveClick = () => {
		// TODO: [lite-contact-info] POST phone number to API if it has changed
		// TODO: [lite-contact-info] POST address to API
	};

	return (
		<FullContainer>
			<TopBar
				title={ __(
					'Add contact information',
					'google-listings-and-ads'
				) }
				helpButton={
					<HelpIconButton eventContext="edit-contact-information" />
				}
				backHref={ getSettingsUrl() }
			/>
			<div className="gla-settings">
				<ContactInformation
					view="settings"
					onPhoneNumberChange={ handlePhoneNumberChange }
				/>
				<Section>
					<Flex justify="flex-end">
						<AppButton isPrimary onClick={ handleSaveClick }>
							{ __( 'Save details', 'google-listings-and-ads' ) }
						</AppButton>
					</Flex>
				</Section>
			</div>
		</FullContainer>
	);
}
