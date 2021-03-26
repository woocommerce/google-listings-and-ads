/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import AppDocumentationLink from '.~/components/app-documentation-link';
import FormContent from './form-content';
import AppButton from '.~/components/app-button';

const CreateCampaign = ( props ) => {
	const { formProps, onContinue = () => {} } = props;

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
								context="setup-ads"
								linkId="see-what-ads-look-like"
								href="https://support.google.com/google-ads/answer/6275294"
							/>
						),
					}
				) }
			/>
			<FormContent
				formProps={ formProps }
				submitButton={
					<AppButton isPrimary onClick={ onContinue }>
						{ __( 'Continue', 'google-listings-and-ads' ) }
					</AppButton>
				}
			/>
		</StepContent>
	);
};

export default CreateCampaign;
