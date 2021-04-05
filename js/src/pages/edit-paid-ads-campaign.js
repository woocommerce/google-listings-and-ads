/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { getQuery, getNewPath } from '@woocommerce/navigation';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import FullContainer from '.~/components/full-container';
import TopBar from '.~/components/stepper/top-bar';
import useApiFetchEffect from '.~/hooks/useApiFetchEffect';
import AppSpinner from '.~/components/app-spinner';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import AppDocumentationLink from '.~/components/app-documentation-link';

const dashboardURL = getNewPath( {}, '/google/dashboard' );

const EditPaidAdsCampaign = () => {
	const { programId } = getQuery();
	const { loading, error, data: campaignData } = useApiFetchEffect( {
		path: `/wc/gla/ads/campaigns/${ programId }`,
	} );

	if ( loading ) {
		return (
			<FullContainer>
				<TopBar
					title={ __( 'Loading…', 'google-listings-and-ads' ) }
					backHref={ dashboardURL }
				/>
				<AppSpinner />
			</FullContainer>
		);
	}

	if ( error ) {
		return (
			<FullContainer>
				<TopBar
					title={ __( 'Edit Campaign', 'google-listings-and-ads' ) }
					backHref={ dashboardURL }
				/>
				<div>
					{ __(
						'Error in loading your paid ads campaign. Please try again later.',
						'google-listings-and-ads'
					) }
				</div>
			</FullContainer>
		);
	}

	return (
		<FullContainer>
			<TopBar
				title={ sprintf(
					// translators: %s: campaign's name.
					__( 'Edit %s', 'google-listings-and-ads' ),
					campaignData.name
				) }
				backHref={ dashboardURL }
			/>
			<StepContent>
				<StepContentHeader
					title={ __(
						'Edit your paid campaign',
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
									context="edit-ads"
									linkId="see-what-ads-look-like"
									href="https://support.google.com/google-ads/answer/6275294"
								/>
							),
						}
					) }
				/>
			</StepContent>
		</FullContainer>
	);
};

export default EditPaidAdsCampaign;
