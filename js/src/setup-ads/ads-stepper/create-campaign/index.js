/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/step-content';
import StepContentHeader from '.~/components/step-content-header';
import StepContentFooter from '.~/components/step-content-footer';
import AppDocumentationLink from '.~/components/app-documentation-link';
import AudienceSection from './audience-section';

const CreateCampaign = () => {
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
			<AudienceSection />
			<StepContentFooter>
				<Button isPrimary>
					{ __( 'Launch campaign', 'google-listings-and-ads' ) }
				</Button>
			</StepContentFooter>
		</StepContent>
	);
};

export default CreateCampaign;
