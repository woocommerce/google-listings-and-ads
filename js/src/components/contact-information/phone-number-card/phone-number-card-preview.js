/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { APPEARANCE } from '.~/components/account-card';
import useGoogleMCPhoneNumber from '.~/hooks/useGoogleMCPhoneNumber';
import ContactInformationPreviewCard from '../contact-information-preview-card';

/**
 * Renders a component with the MC's phone number.
 * In preview mode, meaning there will be no editing features, just the number and edit link.
 *
 * @fires gla_edit_mc_phone_number Whenever "Edit" is clicked.
 *
 * @param {Object} props React props
 * @param {string} props.editHref URL where Edit button should point to.
 * @param {JSX.Element} props.learnMore Link to be shown at the end of missing data message.
 * @return {JSX.Element} Filled AccountCard component.
 */
export function PhoneNumberCardPreview( { editHref, learnMore } ) {
	const { loaded, data } = useGoogleMCPhoneNumber();
	let content, warning;

	// Mock invalid data for testing.
	data.isValid = false;

	if ( loaded ) {
		if ( data.isValid ) {
			content = data.display;
		} else {
			warning = __(
				'Please add your phone number',
				'google-listings-and-ads'
			);
			content = (
				<>
					{ __(
						'Google requires the phone number for all stores using Google Merchant Center. ',
						'google-listings-and-ads'
					) }
					{ learnMore }
				</>
			);
		}
	}

	return (
		<ContactInformationPreviewCard
			appearance={ APPEARANCE.PHONE }
			editHref={ editHref }
			editEventName="gla_edit_mc_phone_number"
			learnMore={ learnMore }
			loading={ ! loaded }
			warning={ warning }
			content={ content }
		></ContactInformationPreviewCard>
	);
}
