/**
 * External dependencies
 */
import { getQuery, getNewPath } from '@woocommerce/navigation';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TopBar from '.~/components/stepper/top-bar';
import useLayout from '.~/hooks/useLayout';
import useApiFetchEffect from '.~/hooks/useApiFetchEffect';
import AppSpinner from '.~/components/app-spinner';
import EditPaidAdsCampaignForm from './edit-paid-ads-campaign-form';
import HelpIconButton from '.~/components/help-icon-button';

const dashboardURL = getNewPath( {}, '/google/dashboard', {} );
const helpButton = <HelpIconButton eventContext="edit-ads" />;

const EditPaidAdsCampaign = () => {
	useLayout( 'full-content' );

	const { programId } = getQuery();
	const { loading, error, data: campaignData } = useApiFetchEffect( {
		path: `/wc/gla/ads/campaigns/${ programId }`,
	} );

	if ( loading ) {
		return (
			<>
				<TopBar
					title={ __( 'Loading…', 'google-listings-and-ads' ) }
					helpButton={ helpButton }
					backHref={ dashboardURL }
				/>
				<AppSpinner />
			</>
		);
	}

	if ( error ) {
		return (
			<>
				<TopBar
					title={ __( 'Edit Campaign', 'google-listings-and-ads' ) }
					helpButton={ helpButton }
					backHref={ dashboardURL }
				/>
				<div>
					{ __(
						'Error in loading your paid ads campaign. Please try again later.',
						'google-listings-and-ads'
					) }
				</div>
			</>
		);
	}

	return (
		<>
			<TopBar
				title={ sprintf(
					// translators: %s: campaign's name.
					__( 'Edit %s', 'google-listings-and-ads' ),
					campaignData.name
				) }
				helpButton={ helpButton }
				backHref={ dashboardURL }
			/>
			<EditPaidAdsCampaignForm campaign={ campaignData } />
		</>
	);
};

export default EditPaidAdsCampaign;
