/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppInputNumberControl from '.~/components/app-input-number-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AudienceCountrySelect from '.~/components/audience-country-select';
import { useAppDispatch } from '.~/data';
import validateShippingTime from '.~/utils/validateShippingTime';
import useGetRemainingCountryCodes from './useGetRemainingCountryCodes';

const AddTimeModal = ( props ) => {
	const { onRequestClose } = props;
	const { upsertShippingTimes } = useAppDispatch();
	const remainingCountryCodes = useGetRemainingCountryCodes();
	const [ dropdownVisible, setDropdownVisible ] = useState( false );

	const handleSubmitCallback = ( values ) => {
		upsertShippingTimes( values );

		onRequestClose();
	};

	return (
		<Form
			initialValues={ {
				countryCodes: remainingCountryCodes,
				time: 0,
			} }
			validate={ validateShippingTime }
			onSubmit={ handleSubmitCallback }
		>
			{ ( formProps ) => {
				const { getInputProps, isValidForm, handleSubmit } = formProps;

				return (
					<AppModal
						overflow="visible"
						shouldCloseOnEsc={ ! dropdownVisible }
						shouldCloseOnClickOutside={ ! dropdownVisible }
						title={ __(
							'Estimate shipping time',
							'google-listings-and-ads'
						) }
						buttons={ [
							<Button
								key="save"
								isPrimary
								disabled={ ! isValidForm }
								onClick={ handleSubmit }
							>
								{ __( 'Save', 'google-listings-and-ads' ) }
							</Button>,
						] }
						onRequestClose={ onRequestClose }
					>
						<VerticalGapLayout>
							<AudienceCountrySelect
								label={ __(
									'If customer is in',
									'google-listings-and-ads'
								) }
								onDropdownVisibilityChange={
									setDropdownVisible
								}
								{ ...getInputProps( 'countryCodes' ) }
							/>
							<AppInputNumberControl
								label={ __(
									'Then the estimated shipping time displayed in the product listing is',
									'google-listings-and-ads'
								) }
								suffix={ __(
									'days',
									'google-listings-and-ads'
								) }
								{ ...getInputProps( 'time' ) }
							/>
						</VerticalGapLayout>
					</AppModal>
				);
			} }
		</Form>
	);
};

export default AddTimeModal;
