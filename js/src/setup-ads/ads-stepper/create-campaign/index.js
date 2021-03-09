/**
 * External dependencies
 */
import { Form } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import AppDocumentationLink from '.~/components/app-documentation-link';
import FormContent from './form-content';

const CreateCampaign = () => {
	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	// TODO: handle form submit.
	const handleSubmitCallback = () => {};

	return (
		<StepContent>
			<StepContentHeader
				step={ __( 'STEP TWO', 'google-listings-and-ads' ) }
				title={ __(
					'Create your paid campaign',
					'google-listings-and-ads'
				) }
				description={ createInterpolateElement(
					__(
						'Paid Smart Shopping campaigns are automatically optimized for you by Google. <link>See what your ads will look like.</link>',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								// TODO: make sure the below URL and trackings are correct.
								context="setup-ads"
								linkId="see-what-ads-look-like"
								href="https://support.google.com/merchants"
							/>
						),
					}
				) }
			/>
			<Form
				initialValues={ {
					name: '',
					amount: '',
					country: '',
				} }
				validate={ handleValidate }
				onSubmitCallback={ handleSubmitCallback }
			>
				{ ( formProps ) => {
					return <FormContent formProps={ formProps } />;
				} }
			</Form>
		</StepContent>
	);
};

export default CreateCampaign;
