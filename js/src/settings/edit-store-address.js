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
import useLayout from '.~/hooks/useLayout';
import useStoreAddress from '.~/hooks/useStoreAddress';
import TopBar from '.~/components/stepper/top-bar';
import HelpIconButton from '.~/components/help-icon-button';
import Section from '.~/wcdl/section';
import AppButton from '.~/components/app-button';

import AppDocumentationLink from '.~/components/app-documentation-link';
import StoreAddressCard from '.~/components/contact-information/store-address-card';

const learnMoreLinkId = 'contact-information-read-more';
const learnMoreUrl =
	'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information';

/**
 * Triggered when the save button in contact information page is clicked.
 *
 * @event gla_contact_information_save_button_click
 */

/**
 * Renders the store address settings page.
 *
 * @see StoreAddressCard
 * @fires gla_contact_information_save_button_click
 * @fires gla_documentation_link_click with `{ context: "settings-store-address", link_id: "contact-information-read-more", href: "https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information" }`
 */
const EditStoreAddress = () => {
	useLayout( 'full-content' );

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
		<>
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
					<StoreAddressCard
						showValidation={ ! address.isAddressFilled }
					/>
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
		</>
	);
};

export default EditStoreAddress;
