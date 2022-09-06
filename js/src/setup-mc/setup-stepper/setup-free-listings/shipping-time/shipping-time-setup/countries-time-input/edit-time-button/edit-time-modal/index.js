/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Form from '.~/components/form';
import AppModal from '.~/components/app-modal';
import AppInputNumberControl from '.~/components/app-input-number-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AudienceCountrySelect from '.~/components/audience-country-select';
import { useAppDispatch } from '.~/data';
import validateShippingTimeGroup from '.~/utils/validateShippingTimeGroup';

const EditTimeModal = ( props ) => {
	const { time: groupedTime, onRequestClose } = props;
	const { upsertShippingTimes, deleteShippingTimes } = useAppDispatch();
	const [ dropdownVisible, setDropdownVisible ] = useState( false );

	const handleDeleteClick = () => {
		deleteShippingTimes( groupedTime.countries );

		onRequestClose();
	};

	const handleSubmitCallback = ( values ) => {
		upsertShippingTimes( {
			countryCodes: values.countries,
			time: values.time,
		} );

		const valuesCountrySet = new Set( values.countries );
		const deletedCountryCodes = groupedTime.countries.filter(
			( el ) => ! valuesCountrySet.has( el )
		);
		if ( deletedCountryCodes.length ) {
			deleteShippingTimes( deletedCountryCodes );
		}

		onRequestClose();
	};

	return (
		<Form
			initialValues={ {
				countries: groupedTime.countries,
				time: groupedTime.time,
			} }
			validate={ validateShippingTimeGroup }
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
								key="delete"
								isTertiary
								isDestructive
								onClick={ handleDeleteClick }
							>
								{ __( 'Delete', 'google-listings-and-ads' ) }
							</Button>,
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
								{ ...getInputProps( 'countries' ) }
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

export default EditTimeModal;
