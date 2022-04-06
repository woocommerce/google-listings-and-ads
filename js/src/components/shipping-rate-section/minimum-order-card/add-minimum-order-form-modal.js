/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppInputPriceControl from '.~/components/app-input-price-control/index.js';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AppCountrySelect from '.~/components/app-country-select';

/**
 * Display the add minimum order modal that is wrapped in a Form.
 *
 * When users submit the form, `props.onRequestClose` will be called first, and then followed by `props.onSubmit`.
 * If we were to call `props.onSubmit` first, it may cause some state change in the parent component and causing this component not to be rendered,
 * and when `props.onRequestClose` is called later, there would be a runtime React error because the component is no longer there.
 *
 * @param {Object} props Props.
 * @param {Array<string>} props.countryOptions Array of country codes options, to be used as options in AppCountrySelect.
 * @param {Object} props.initialValues Initial values for the form.
 * @param {Array<string>} props.initialValues.countries Array of selected country codes.
 * @param {string} props.initialValues.currency Selected currency.
 * @param {number} props.initialValues.threshold Threshold value.
 * @param {function()} props.onRequestClose Callback to close the modal.
 * @param {function(Object)} props.onSubmit Callback when the form is submitted, with the form value.
 */
const AddMinimumOrderFormModal = ( props ) => {
	const {
		countryOptions,
		initialValues,
		onSubmit = noop,
		onRequestClose = noop,
	} = props;

	const handleValidate = ( values ) => {
		const errors = {};

		if ( values.countries.length === 0 ) {
			errors.countries = __(
				'Please specify at least one country.',
				'google-listings-and-ads'
			);
		}

		if ( ! ( values.threshold > 0 ) ) {
			errors.price = __(
				'The minimum order amount must be greater than 0.',
				'google-listings-and-ads'
			);
		}

		return errors;
	};

	const handleSubmitCallback = ( newValue ) => {
		onRequestClose();
		onSubmit( newValue );
	};

	return (
		<Form
			initialValues={ initialValues }
			validate={ handleValidate }
			onSubmit={ handleSubmitCallback }
		>
			{ ( formProps ) => {
				const {
					getInputProps,
					values,
					setValue,
					isValidForm,
					handleSubmit,
				} = formProps;

				return (
					<AppModal
						title={ __(
							'Minimum order to qualify for free shipping',
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
									'Add minimum order',
									'google-listings-and-ads'
								) }
							</Button>,
						] }
						onRequestClose={ onRequestClose }
					>
						<VerticalGapLayout>
							<AppCountrySelect
								label={ __(
									'If customer is in',
									'google-listings-and-ads'
								) }
								options={ countryOptions }
								multiple
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

export default AddMinimumOrderFormModal;
