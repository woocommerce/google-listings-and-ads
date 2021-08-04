/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useGoogleMCPhoneNumber from '.~/hooks/useGoogleMCPhoneNumber';
import Section from '.~/wcdl/section';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AppDocumentationLink from '.~/components/app-documentation-link';
import AppSpinner from '.~/components/app-spinner';
import PhoneNumberCard from './phone-number-card';
import StoreAddressCard from './store-address-card';

const description = __(
	'Your contact information is required by Google for verification purposes. It will be shared with the Google Merchant Center and will not be displayed to customers.',
	'google-listings-and-ads'
);

const mcTitle = __( 'Enter contact information', 'google-listings-and-ads' );
const settingsTitle = __( 'Contact information', 'google-listings-and-ads' );

export default function ContactInformation( { view, onPhoneNumberChange } ) {
	const phone = useGoogleMCPhoneNumber();
	const isSetupMC = view === 'setup-mc';

	const initEditing = isSetupMC ? ! phone.data.isValid : true;
	const title = isSetupMC ? mcTitle : settingsTitle;
	const trackContext = isSetupMC
		? 'setup-mc-contact-information'
		: 'settings-contact-information';

	return (
		<Section
			title={ title }
			description={
				<div>
					<p>{ description }</p>
					<p>
						<AppDocumentationLink
							context={ trackContext }
							linkId="contact-information-read-more"
							// TODO: [lite-contact-info] add link
							href="https://example.com/"
						>
							{ __( 'Learn more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</div>
			}
		>
			{ phone.loaded ? (
				<VerticalGapLayout size="large">
					<PhoneNumberCard
						phoneNumber={ phone }
						initEditing={ initEditing }
						onPhoneNumberChange={ onPhoneNumberChange }
					/>
					<StoreAddressCard />
				</VerticalGapLayout>
			) : (
				<AppSpinner />
			) }
		</Section>
	);
}
