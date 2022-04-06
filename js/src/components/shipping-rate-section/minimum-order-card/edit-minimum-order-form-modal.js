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
import AppInputPriceControl from '.~/components/app-input-price-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AppCountrySelect from '.~/components/app-country-select';

/**
 * @param {Object} props Props.
 * @param {Array<string>} props.countryOptions Array of country codes options, to be used as options in AppCountrySelect.
 * @param {Object} props.initialValues Initial values for the form.
 * @param {Array<string>} props.initialValues.countries Array of selected country codes.
 * @param {string} props.initialValues.currency Selected currency.
 * @param {number} props.initialValues.threshold Threshold value.
 */
const EditMinimumOrderFormModal = ( props ) => {
	const {
		countryOptions,
		initialValues,
		onSubmit = noop,
		onRequestClose = noop,
	} = props;

	const handleDeleteClick = () => {
		onRequestClose();
		onSubmit( {
			countries: [],
			currency: undefined,
			threshold: undefined,
		} );
	};

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
								{ __(
									'Update minimum order',
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

export default EditMinimumOrderFormModal;
