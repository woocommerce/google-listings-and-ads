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
import SupportedCountrySelect from '.~/components/supported-country-select';
import validateShippingTimeGroup from '.~/utils/validateShippingTimeGroup';

/**
 * Form to add a new time for selected country(-ies).
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countries A list of country codes to choose from.
 * @param {Function} props.onRequestClose
 * @param {function(AggregatedShippingTime): void} props.onSubmit Called with submitted value.
 */
const AddTimeModal = ( { countries, onRequestClose, onSubmit } ) => {
	const [ dropdownVisible, setDropdownVisible ] = useState( false );

	const handleSubmitCallback = ( values ) => {
		onSubmit( values );
		onRequestClose();
	};

	return (
		<Form
			initialValues={ {
				countries,
				time: 0,
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
								key="save"
								isPrimary
								disabled={ ! isValidForm }
								onClick={ handleSubmit }
							>
								{ __(
									'Add shipping time',
									'google-listings-and-ads'
								) }
							</Button>,
						] }
						onRequestClose={ onRequestClose }
					>
						<VerticalGapLayout>
							<SupportedCountrySelect
								label={ __(
									'If customer is in',
									'google-listings-and-ads'
								) }
								countryCodes={ countries }
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

export default AddTimeModal;

/**
 * @typedef { import(".~/data/actions").AggregatedShippingTime } AggregatedShippingTime
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */
