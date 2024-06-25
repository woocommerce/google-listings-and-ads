/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Stepper } from '@woocommerce/components';
import { useState, useRef } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';

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
import {
	CAMPAIGN_STEP as STEP,
	CAMPAIGN_STEP_NUMBER_MAP as STEP_NUMBER_MAP,
} from '.~/constants';
import { API_NAMESPACE } from '.~/data/constants';
import {
	recordStepperChangeEvent,
	recordStepContinueEvent,
} from '.~/utils/tracks';

const eventName = 'gla_paid_campaign_step';
const eventContext = 'create-ads';
const dashboardURL = getDashboardUrl();

/**
 * Renders the campaign creation page.
 *
 * @fires gla_paid_campaign_step with `{ context: 'create-ads', triggered_by: 'step1-continue-button', action: 'go-to-step2' }`.
 * @fires gla_paid_campaign_step with `{ context: 'create-ads', triggered_by: 'stepper-step1-button', action: 'go-to-step1' }`.
 */
const CreatePaidAdsCampaign = () => {
	useLayout( 'full-content' );

	const [ step, setStep ] = useState( STEP.CAMPAIGN );
	const createdCampaignIdRef = useRef( null );
	const { createAdsCampaign, updateCampaignAssetGroup } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const { data: initialCountryCodes } = useTargetAudienceFinalCountryCodes();

	const handleStepperClick = ( nextStep ) => {
		recordStepperChangeEvent(
			eventName,
			STEP_NUMBER_MAP[ nextStep ],
			eventContext
		);
		setStep( nextStep );
	};

	const handleContinueClick = ( nextStep ) => {
		recordStepContinueEvent(
			eventName,
			STEP_NUMBER_MAP[ step ],
			STEP_NUMBER_MAP[ nextStep ],
			eventContext
		);
		setStep( nextStep );
	};

	const handleSubmit = async ( values, enhancer ) => {
		const { action } = enhancer.submitter.dataset;

		try {
			const { amount, countryCodes } = values;

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

		// Add a query param `campaign=saved` to the dashboard URL to indicate that the campaign was successfully created and saved.
		getHistory().push( getDashboardUrl( { campaign: 'saved' } ) );
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
				helpButton={ <HelpIconButton eventContext={ eventContext } /> }
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
									trackingContext={ eventContext }
									onContinue={ () =>
										handleContinueClick( STEP.ASSET_GROUP )
									}
								/>
							),
							onClick: handleStepperClick,
						},
						{
							key: STEP.ASSET_GROUP,
							label: __(
								'Optimize your campaign',
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
