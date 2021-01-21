/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppModal from '../../../../../../../components/app-modal';
import AppInputControl from '../../../../../../../components/app-input-control';
import AppCountryMultiSelect from '../../../../../../../components/app-country-multi-select';
import VerticalGapLayout from '../../../../components/vertical-gap-layout';
import useStoreCurrency from '../../../../../../../hooks/useStoreCurrency';

const AddRateModal = ( props ) => {
	const { onRequestClose } = props;
	const { code } = useStoreCurrency();

	// TODO: get list of countries without price.
	const countriesWithoutPrice = [
		{
			key: 'USA',
			label: 'United States of America',
			value: { id: 'USA' },
		},
	];

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	// TODO: call backend API when submit form.
	const handleSubmitCallback = () => {
		onRequestClose();
	};

	return (
		<Form
			initialValues={ {
				countries: countriesWithoutPrice,
				currency: code,
				price: '',
			} }
			validate={ handleValidate }
			onSubmitCallback={ handleSubmitCallback }
		>
			{ ( formProps ) => {
				const { getInputProps, handleSubmit } = formProps;

				return (
					<AppModal
						className="gla-edit-rate-modal"
						title={ __(
							'Estimate a shipping rate',
							'google-listings-and-ads'
						) }
						buttons={ [
							<Button
								key="save"
								isPrimary
								onClick={ handleSubmit }
							>
								{ __( 'Save', 'google-listings-and-ads' ) }
							</Button>,
						] }
						onRequestClose={ onRequestClose }
					>
						<VerticalGapLayout>
							<div>
								<div className="label">
									{ __(
										'If customer is in',
										'google-listings-and-ads'
									) }
								</div>
								<AppCountryMultiSelect
									{ ...getInputProps( 'countries' ) }
								/>
							</div>
							<AppInputControl
								label={ __(
									'Then the estimated shipping rate displayed in the product listing is',
									'google-listings-and-ads'
								) }
								suffix={ code }
								{ ...getInputProps( 'price' ) }
							/>
						</VerticalGapLayout>
					</AppModal>
				);
			} }
		</Form>
	);
};

export default AddRateModal;
