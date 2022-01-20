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
import AppInputNumberControl from '.~/components/app-input-number-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AudienceCountrySelect from '.~/components/audience-country-select';
import './index.scss';
import { useAppDispatch } from '.~/data';

const EditTimeModal = ( props ) => {
	const { time: groupedTime, onRequestClose } = props;
	const { upsertShippingTimes, deleteShippingTimes } = useAppDispatch();

	const handleDeleteClick = () => {
		deleteShippingTimes( groupedTime.countries );

		onRequestClose();
	};

	const handleValidate = ( values ) => {
		const errors = {};

		if ( values.countryCodes.length === 0 ) {
			errors.countryCodes = __(
				'Please specify at least one country.',
				'google-listings-and-ads'
			);
		}

		if ( values.time === null ) {
			errors.time = __(
				'Please enter the estimated shipping time.',
				'google-listings-and-ads'
			);
		}

		if ( values.time < 0 ) {
			errors.time = __(
				'The estimated shipping time cannot be less than 0.',
				'google-listings-and-ads'
			);
		}

		return errors;
	};

	const handleSubmitCallback = ( values ) => {
		upsertShippingTimes( values );

		const valuesCountrySet = new Set( values.countryCodes );
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
				countryCodes: groupedTime.countries,
				time: groupedTime.time,
			} }
			validate={ handleValidate }
			onSubmit={ handleSubmitCallback }
		>
			{ ( formProps ) => {
				const { getInputProps, isValidForm, handleSubmit } = formProps;

				return (
					<AppModal
						className="gla-edit-time-modal"
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
							<div>
								<div className="label">
									{ __(
										'If customer is in',
										'google-listings-and-ads'
									) }
								</div>
								<AudienceCountrySelect
									multiple
									{ ...getInputProps( 'countryCodes' ) }
								/>
							</div>
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
