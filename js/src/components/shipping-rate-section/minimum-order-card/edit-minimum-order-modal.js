/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppInputPriceControl from '.~/components/app-input-price-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import SupportedCountrySelect from '.~/components/supported-country-select';

const EditMinimumOrderModal = ( props ) => {
	const { countryOptions, value, onChange, onRequestClose } = props;

	const handleDeleteClick = () => {
		onRequestClose();
		onChange( {
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
		onChange( newValue );
	};

	return (
		<Form
			initialValues={ {
				countries: value.countries,
				currency: value.currency,
				threshold: value.threshold,
			} }
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
						overflowY="visible"
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
							<SupportedCountrySelect
								label={ __(
									'If customer is in',
									'google-listings-and-ads'
								) }
								countryCodes={ countryOptions }
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

export default EditMinimumOrderModal;
