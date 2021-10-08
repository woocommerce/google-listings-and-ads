/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getHistory } from '@woocommerce/navigation';
import { useState } from '@wordpress/element';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getSettingsUrl } from '.~/utils/urls';
import { useAppDispatch } from '.~/data';
import useStoreAddress from '.~/hooks/useStoreAddress';
import FullContainer from '.~/components/full-container';
import TopBar from '.~/components/stepper/top-bar';
import HelpIconButton from '.~/components/help-icon-button';
import Section from '.~/wcdl/section';
import AppButton from '.~/components/app-button';

import AppDocumentationLink from '.~/components/app-documentation-link';
import StoreAddressCard from '.~/components/contact-information/store-address-card';

const learnMoreLinkId = 'contact-information-read-more';
const learnMoreUrl =
	'https://docs.woocommerce.com/document/google-listings-and-ads/#contact-information';

/**
 * Renders the store address settings page.
 *
 * @see StoreAddressCard
 */
export default function EditStoreAddress() {
	const { updateGoogleMCContactInformation } = useAppDispatch();
	const { data: address } = useStoreAddress();
	const [ isSaving, setSaving ] = useState( false );

	const handleSaveClick = () => {
		setSaving( true );
		updateGoogleMCContactInformation()
			.then( () => getHistory().push( getSettingsUrl() ) )
			.catch( () => setSaving( false ) );
	};

	const isReadyToSave =
		address.isAddressFilled && address.isMCAddressDifferent;

	return (
		<FullContainer>
			<TopBar
				title={ __( 'Edit store address', 'google-listings-and-ads' ) }
				helpButton={
					<HelpIconButton eventContext="edit-store-address" />
				}
				backHref={ getSettingsUrl() }
			/>
			<div className="gla-settings">
				<Section
					title={ __( 'Store address', 'google-listings-and-ads' ) }
					description={
						<div>
							<p>
								{ __(
									'Your store address is required by Google for verification purposes. It will be shared with the Google Merchant Center and will not be displayed to customers.',
									'google-listings-and-ads'
								) }
							</p>
							<p>
								<AppDocumentationLink
									context="settings-store-address"
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
					<StoreAddressCard />
				</Section>
				<Section>
					<Flex justify="flex-end">
						<AppButton
							isPrimary
							loading={ isSaving }
							disabled={ ! isReadyToSave }
							eventName="gla_contact_information_save_button_click"
							onClick={ handleSaveClick }
						>
							{ __( 'Save details', 'google-listings-and-ads' ) }
						</AppButton>
					</Flex>
				</Section>
			</div>
		</FullContainer>
	);
}
