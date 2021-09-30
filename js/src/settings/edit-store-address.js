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
import ContactInformation from '.~/components/contact-information';

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
				<ContactInformation view="settings" />
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
