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
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AppDocumentationLink from '.~/components/app-documentation-link';
import CreateCampaignFormContent from '.~/components/paid-ads/create-campaign-form-content';
import AppButton from '.~/components/app-button';

/**
 * @fires gla_documentation_link_click with `{ context: 'setup-ads', link_id: 'see-what-ads-look-like', href: 'https://support.google.com/google-ads/answer/6275294' }`
 */

const CreateCampaign = ( props ) => {
	const { formProps, onContinue = () => {} } = props;
	const { isValidForm } = formProps;

	return (
		<StepContent>
			<StepContentHeader
				title={ __(
					'Create your paid campaign',
					'google-listings-and-ads'
				) }
				description={ createInterpolateElement(
					__(
						'Paid Performance Max campaigns are automatically optimized for you by Google. <link>See what your ads will look like.</link>',
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
			<CreateCampaignFormContent formProps={ formProps } />
			<StepContentFooter>
				<AppButton
					isPrimary
					disabled={ ! isValidForm }
					onClick={ onContinue }
				>
					{ __( 'Continue', 'google-listings-and-ads' ) }
				</AppButton>
			</StepContentFooter>
		</StepContent>
	);
};

export default CreateCampaign;
