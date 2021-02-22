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
import AppInputControl from '.~/components/app-input-control';
import VerticalGapLayout from '.~/components/edit-program/vertical-gap-layout';
import AudienceCountrySelect from '../../../../../components/audience-country-select';
import './index.scss';

const EditTimeModal = ( props ) => {
	const { time, onRequestClose } = props;

	// TODO: call API to delete the time.
	const handleDeleteClick = () => {
		onRequestClose();
	};

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
				countries: time.countries,
				time: time.time,
			} }
			validate={ handleValidate }
			onSubmitCallback={ handleSubmitCallback }
		>
			{ ( formProps ) => {
				const { getInputProps, handleSubmit } = formProps;

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
									{ ...getInputProps( 'countries' ) }
								/>
							</div>
							<AppInputControl
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
