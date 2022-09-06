/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Form from '.~/components/form';
import AppModal from '.~/components/app-modal';
import AppInputPriceControl from '.~/components/app-input-price-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import SupportedCountrySelect from '.~/components/supported-country-select';
import validateMinimumOrder from './validateMinimumOrder';

/**
 * @typedef { import("@wordpress/components").Button } Button
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("../typedefs.js").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Minimum order modal that is wrapped in a form.
 *
 * Buttons can be customized by using the `renderButtons` prop.
 * The render function will be passed `formProps`,
 * allowing the buttons to have access to form's `isValidForm` and `handleSubmit`.
 *
 * @param {Object} props Props.
 * @param {Array<CountryCode>} props.countryOptions Array of country codes options, to be used as options in SupportedCountrySelect.
 * @param {(formProps: Object) => Array<Button>} props.renderButtons Function to render buttons for the modal. `formProps` will be passed into this render function.
 * @param {MinimumOrderGroup} props.initialValues Initial values for the form.
 * @param {(values: MinimumOrderGroup) => void} props.onSubmit Callback when the form is submitted, with the form value.
 * @param {() => void} props.onRequestClose Callback to close the modal.
 */
const MinimumOrderFormModal = ( {
	countryOptions,
	renderButtons,
	initialValues,
	onSubmit,
	onRequestClose,
} ) => {
	const [ dropdownVisible, setDropdownVisible ] = useState( false );

	return (
		<Form
			initialValues={ initialValues }
			validate={ validateMinimumOrder }
			onSubmit={ onSubmit }
		>
			{ ( formProps ) => {
				const { getInputProps, values, setValue } = formProps;

				return (
					<AppModal
						overflow="visible"
						shouldCloseOnEsc={ ! dropdownVisible }
						shouldCloseOnClickOutside={ ! dropdownVisible }
						title={ __(
							'Minimum order to qualify for free shipping',
							'google-listings-and-ads'
						) }
						buttons={ renderButtons( formProps ) }
						onRequestClose={ onRequestClose }
					>
						<VerticalGapLayout>
							<SupportedCountrySelect
								label={ __(
									'If customer is in',
									'google-listings-and-ads'
								) }
								countryCodes={ countryOptions }
								onDropdownVisibilityChange={
									setDropdownVisible
								}
								{ ...getInputProps( 'countries' ) }
							/>
							<AppInputPriceControl
								label={ __(
									'Then they qualify for free shipping if their order is over',
									'google-listings-and-ads'
								) }
								suffix={ values.currency }
								{ ...getInputProps( 'threshold' ) }
								onBlur={ ( event, numberValue ) => {
									getInputProps( 'threshold' ).onBlur(
										event
									);
									setValue(
										'threshold',
										numberValue > 0
											? numberValue
											: undefined
									);
								} }
							/>
						</VerticalGapLayout>
					</AppModal>
				);
			} }
		</Form>
	);
};

export default MinimumOrderFormModal;
