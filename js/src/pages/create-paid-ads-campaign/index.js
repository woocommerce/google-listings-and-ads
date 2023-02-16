/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Stepper } from '@woocommerce/components';
import { useState, useRef } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import useLayout from '.~/hooks/useLayout';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import { useAppDispatch } from '.~/data';
import { getDashboardUrl } from '.~/utils/urls';
import convertToAssetGroupUpdateBody from '.~/components/paid-ads/convertToAssetGroupUpdateBody';
import TopBar from '.~/components/stepper/top-bar';
import HelpIconButton from '.~/components/help-icon-button';
import CampaignAssetsForm from '.~/components/paid-ads/campaign-assets-form';
import AdsCampaign from '.~/components/paid-ads/ads-campaign';
import AssetGroup, {
	ACTION_SUBMIT_CAMPAIGN_AND_ASSETS,
} from '.~/components/paid-ads/asset-group';
import { CAMPAIGN_STEP as STEP } from '.~/constants';
import { API_NAMESPACE } from '.~/data/constants';

const dashboardURL = getDashboardUrl();

/**
 * Renders the campaign creation page.
 *
 * @fires gla_launch_paid_campaign_button_click on submit
 */
const CreatePaidAdsCampaign = () => {
	useLayout( 'full-content' );

	const [ step, setStep ] = useState( STEP.CAMPAIGN );
	const createdCampaignIdRef = useRef( null );
	const { createAdsCampaign, updateCampaignAssetGroup } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const { data: initialCountryCodes } = useTargetAudienceFinalCountryCodes();

	const handleSubmit = async ( values, enhancer ) => {
		const { action } = enhancer.submitter.dataset;

		try {
			const { amount, countryCodes } = values;
			recordEvent( 'gla_launch_paid_campaign_button_click', {
				audiences: countryCodes.join( ',' ),
				budget: amount,
			} );

			// Avoid re-creating a new campaign if the subsequent asset group update is failed.
			if ( createdCampaignIdRef.current === null ) {
				const payload = await createAdsCampaign( amount, countryCodes );
				createdCampaignIdRef.current = payload.createdCampaign.id;
			}

			if ( action === ACTION_SUBMIT_CAMPAIGN_AND_ASSETS ) {
				const id = createdCampaignIdRef.current;
				const path = `${ API_NAMESPACE }/ads/campaigns/asset-groups?campaign_id=${ id }`;

				const [ assetEntityGroup ] = await apiFetch( { path } );

				const body = convertToAssetGroupUpdateBody(
					assetEntityGroup,
					values
				);

				await updateCampaignAssetGroup( assetEntityGroup.id, body );
			}

			createNotice(
				'success',
				__(
					'You’ve successfully created a paid campaign!',
					'google-listings-and-ads'
				)
			);
		} catch ( e ) {
			enhancer.signalFailedSubmission();
			return;
		}

		getHistory().push( getDashboardUrl() );
	};

	if ( ! initialCountryCodes ) {
		return null;
	}

	return (
		<>
			<TopBar
				title={ __(
					'Create your paid campaign',
					'google-listings-and-ads'
				) }
				helpButton={ <HelpIconButton eventContext="create-ads" /> }
				backHref={ dashboardURL }
			/>
			<CampaignAssetsForm
				initialCampaign={ {
					amount: 0,
					countryCodes: initialCountryCodes,
				} }
				onSubmit={ handleSubmit }
			>
				<Stepper
					currentStep={ step }
					steps={ [
						{
							key: STEP.CAMPAIGN,
							label: __(
								'Create paid campaign',
								'google-listings-and-ads'
							),
							content: (
								<AdsCampaign
									trackingContext="create-ads"
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
							content: <AssetGroup />,
						},
					] }
				/>
			</CampaignAssetsForm>
		</>
	);
};

export default CreatePaidAdsCampaign;
