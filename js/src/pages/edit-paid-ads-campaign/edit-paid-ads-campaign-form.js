/**
 * External dependencies
 */
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import EditPaidAdsCampaignFormContent from './edit-paid-ads-campaign-form-content';

const EditPaidAdsCampaignForm = ( props ) => {
	const { campaign } = props;

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	return (
		<Form
			initialValues={ {
				id: campaign.id,
				amount: campaign.amount,
				country: campaign.country,
			} }
			validate={ handleValidate }
		>
			{ ( formProps ) => {
				return (
					<EditPaidAdsCampaignFormContent formProps={ formProps } />
				);
			} }
		</Form>
	);
};

export default EditPaidAdsCampaignForm;
