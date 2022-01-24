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
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import AppSpinner from '.~/components/app-spinner';
import EditPaidAdsCampaignForm from './edit-paid-ads-campaign-form';
import HelpIconButton from '.~/components/help-icon-button';

const dashboardURL = getNewPath( {}, '/google/dashboard', {} );
const helpButton = <HelpIconButton eventContext="edit-ads" />;

function useAdsCampaign( id ) {
	const { loaded, data: campaigns } = useAdsCampaigns();
	const campaign = campaigns?.find( ( el ) => el.id === id );
	return {
		loaded,
		data: campaign || null,
	};
}

const EditPaidAdsCampaign = () => {
	useLayout( 'full-content' );

	const id = Number( getQuery().programId );
	const { loaded, data: campaignData } = useAdsCampaign( id );

	if ( ! loaded ) {
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

	if ( ! campaignData ) {
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
