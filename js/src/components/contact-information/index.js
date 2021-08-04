/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getEditContactInformationUrl } from '.~/utils/urls';
import useGoogleMCPhoneNumber from '.~/hooks/useGoogleMCPhoneNumber';
import Section from '.~/wcdl/section';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AppDocumentationLink from '.~/components/app-documentation-link';
import SpinnerCard from '.~/components/spinner-card';
import PhoneNumberCard from './phone-number-card';
import StoreAddressCard from './store-address-card';
import NoContactInformationCard from './no-contact-information-card';

const learnMoreUrl =
	'https://docs.woocommerce.com/document/google-listings-and-ads/#contact-information';

const description = __(
	'Your contact information is required by Google for verification purposes. It will be shared with the Google Merchant Center and will not be displayed to customers.',
	'google-listings-and-ads'
);

const mcTitle = __( 'Enter contact information', 'google-listings-and-ads' );
const settingsTitle = __( 'Contact information', 'google-listings-and-ads' );

export function ContactInformationPreview() {
	const phone = useGoogleMCPhoneNumber();

	const handleEditClick = () => {
		getHistory().push( getEditContactInformationUrl() );
	};

	let sectionContent;

	if ( phone.loaded ) {
		if ( phone.data.isValid ) {
			sectionContent = (
				<VerticalGapLayout size="overlap">
					<PhoneNumberCard
						isPreview
						initEditing={ false }
						onEditClick={ handleEditClick }
					/>
					<StoreAddressCard isPreview />
				</VerticalGapLayout>
			);
		} else {
			sectionContent = (
				<NoContactInformationCard
					onEditClick={ handleEditClick }
					learnMoreUrl={ learnMoreUrl }
				/>
			);
		}
	} else {
		sectionContent = <SpinnerCard />;
	}

	return (
		<Section title={ settingsTitle } description={ description }>
			{ sectionContent }
		</Section>
	);
}

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
							href={ learnMoreUrl }
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
						initEditing={ initEditing }
						onPhoneNumberChange={ onPhoneNumberChange }
					/>
					<StoreAddressCard />
				</VerticalGapLayout>
			) : (
				<VerticalGapLayout size="large">
					<SpinnerCard />
					<SpinnerCard />
				</VerticalGapLayout>
			) }
		</Section>
	);
}
