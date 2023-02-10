/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Stepper } from '@woocommerce/components';
import { getQuery, getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useLayout from '.~/hooks/useLayout';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import { useAppDispatch } from '.~/data';
import { getDashboardUrl } from '.~/utils/urls';
import TopBar from '.~/components/stepper/top-bar';
import HelpIconButton from '.~/components/help-icon-button';
import CampaignAssetsForm from '.~/components/paid-ads/campaign-assets-form';
import AdsCampaign from '.~/components/paid-ads/ads-campaign';
import AppSpinner from '.~/components/app-spinner';
import AssetGroup, {
	ACTION_SUBMIT_CAMPAIGN_AND_ASSETS,
} from '.~/components/paid-ads/asset-group';
import { CAMPAIGN_STEP as STEP } from '.~/constants';

const dashboardURL = getDashboardUrl();
const helpButton = <HelpIconButton eventContext="edit-ads" />;

function getCurrentStep() {
	const { step } = getQuery();
	if ( Object.values( STEP ).includes( step ) ) {
		return step;
	}
	return STEP.CAMPAIGN;
}

/**
 * Renders the campaign editing page.
 */
const EditPaidAdsCampaign = () => {
	useLayout( 'full-content' );

	const { updateAdsCampaign } = useAppDispatch();

	const id = Number( getQuery().programId );
	const { loaded, data: campaigns } = useAdsCampaigns();
	const campaign = campaigns?.find( ( el ) => el.id === id );

	const setStep = ( step ) => {
		const url = getNewPath( { ...getQuery(), step } );
		getHistory().push( url );
	};

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

	if ( ! campaign ) {
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

	const handleSubmit = async ( values, submitter ) => {
		const { action } = submitter.dataset;
		const { amount } = values;

		try {
			await updateAdsCampaign( campaign.id, { amount } );

			if ( action === ACTION_SUBMIT_CAMPAIGN_AND_ASSETS ) {
				// TODO: Save asset group
			}
		} catch ( e ) {
			return;
		}

		getHistory().push( getDashboardUrl() );
	};

	return (
		<>
			<TopBar
				title={ sprintf(
					// translators: %s: campaign's name.
					__( 'Edit %s', 'google-listings-and-ads' ),
					campaign.name
				) }
				helpButton={ helpButton }
				backHref={ dashboardURL }
			/>
			<CampaignAssetsForm
				initialCampaign={ {
					amount: campaign.amount,
					countryCodes: campaign.displayCountries,
				} }
				onSubmit={ handleSubmit }
			>
				<Stepper
					currentStep={ getCurrentStep() }
					steps={ [
						{
							key: STEP.CAMPAIGN,
							label: __(
								'Edit paid campaign',
								'google-listings-and-ads'
							),
							content: (
								<AdsCampaign
									campaign={ campaign }
									trackingContext="edit-ads"
									onContinue={ () =>
										setStep( STEP.ASSET_GROUP )
									}
								/>
							),
							onClick: setStep,
						},
						{
							key: STEP.ASSET_GROUP,
							label: __(
								'Boost your campaign',
								'google-listings-and-ads'
							),
							content: <AssetGroup campaign={ campaign } />,
						},
					] }
				/>
			</CampaignAssetsForm>
		</>
	);
};

export default EditPaidAdsCampaign;
