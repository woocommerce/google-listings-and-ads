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
import useLayout from '~/hooks/useLayout';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import { useAppDispatch } from '~/data';
import { getDashboardUrl } from '~/utils/urls';
import convertToAssetGroupUpdateBody from '~/components/paid-ads/convertToAssetGroupUpdateBody';
import TopBar from '~/components/stepper/top-bar';
import ContinueButton from '~/components/paid-ads/continue-button';
import HelpIconButton from '~/components/help-icon-button';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import AssetGroup, {
	ACTION_SUBMIT_CAMPAIGN_AND_ASSETS,
} from '~/components/paid-ads/asset-group';
import {
	CAMPAIGN_STEP as STEP,
	CAMPAIGN_STEP_NUMBER_MAP as STEP_NUMBER_MAP,
} from '~/constants';
import { API_NAMESPACE } from '~/data/constants';
import {
	recordGlaEvent,
	recordStepperChangeEvent,
	recordStepContinueEvent,
} from '~/utils/tracks';
import EuPoliticalDeclaration from '~/components/eu-political-declaration';
import useEuPoliticalDeclarationContext from '~/hooks/useEuPoliticalDeclarationContext';
import useApplyCYOIncentive from '~/hooks/useApplyCYOIncentive';

const eventName = 'gla_paid_campaign_step';
const eventContext = 'create-ads';
const dashboardURL = getDashboardUrl();

/**
 * Applying a "Choose Your Own" incentive offer when creating a campaign after onboarding.
 *
 * @event gla_campaign_with_cyo_incentive_selected
 * @property {string} context The context in which the incentive offer is applied, e.g. 'create-ads' or 'setup-ads'.
 * @property {string} level The level of the applied incentive offer, e.g. 'low', 'medium', or 'high'.
 */

/**
 * Renders the campaign creation page.
 *
 * @fires gla_paid_campaign_step with `{ context: 'create-ads', triggered_by: 'step1-continue-button', action: 'go-to-step2' }`.
 * @fires gla_paid_campaign_step with `{ context: 'create-ads', triggered_by: 'stepper-step1-button', action: 'go-to-step1' }`.
 * @fires gla_campaign_with_cyo_incentive_selected with `{ context: 'create-ads', level: 'low' | 'medium' | 'high' }`.
 */
const CreatePaidAdsCampaign = () => {
	useLayout( 'full-content' );
	const { hasGoogleMCConnection } = useGoogleMCAccount();
	const [ step, setStep ] = useState( STEP.CAMPAIGN );
	const createdCampaignIdRef = useRef( null );
	const hasAppliedIncentiveRef = useRef( false );
	const {
		createAdsCampaign,
		createAdsWithAssetsCampaign,
		updateCampaignAssetGroup,
	} = useAppDispatch();
	const { handleError: handleEuPoliticalDeclarationError } =
		useEuPoliticalDeclarationContext();
	const { createNotice } = useDispatchCoreNotices();
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const { applyIncentive } = useApplyCYOIncentive();

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
			const {
				dailyBudget,
				hasConfirmedEuPoliticalContent,
				incentiveOffer,
			} = values;

			// A failed apply doesn't block creating the campaign. Once an offer is applied, a later submit
			// gets `false`, so keep the result for the notice in case creating the campaign is retried.
			const hasAppliedIncentive = await applyIncentive( incentiveOffer );

			if ( hasAppliedIncentive ) {
				hasAppliedIncentiveRef.current = true;
				recordGlaEvent( 'gla_campaign_with_cyo_incentive_selected', {
					context: eventContext,
					level: incentiveOffer,
				} );
			}

			if ( hasGoogleMCConnection ) {
				// Avoid re-creating a new campaign if the subsequent asset group update is failed.
				if ( createdCampaignIdRef.current === null ) {
					const payload = await createAdsCampaign(
						dailyBudget,
						countryCodes,
						hasConfirmedEuPoliticalContent
					);
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
			} else {
				await createAdsWithAssetsCampaign(
					dailyBudget,
					countryCodes,
					convertToAssetGroupUpdateBody(
						{
							final_url: '',
							display_url_path: [ '', '' ],
							assets: {},
						},
						values
					),
					hasConfirmedEuPoliticalContent
				);
			}

			createNotice(
				'success',
				hasAppliedIncentiveRef.current
					? __(
							'Campaign created. Your Ads credit offer is applied to your Google Ads account.',
							'google-listings-and-ads'
						)
					: __(
							'You’ve successfully created a campaign!',
							'google-listings-and-ads'
						)
			);
		} catch ( e ) {
			handleEuPoliticalDeclarationError( e );

			enhancer.signalFailedSubmission();
			return;
		}

		// Add a query param `campaign=saved` to the dashboard URL to indicate that the campaign was successfully created and saved.
		getHistory().push( getDashboardUrl( { campaign: 'saved' } ) );
	};

	if ( ! countryCodes ) {
		return null;
	}

	return (
		<>
			<TopBar
				title={ __(
					'Create your campaign',
					'google-listings-and-ads'
				) }
				helpButton={ <HelpIconButton eventContext={ eventContext } /> }
				backHref={ dashboardURL }
			/>
			<CampaignAssetsForm
				countryCodes={ countryCodes }
				onSubmit={ handleSubmit }
			>
				<Stepper
					currentStep={ step }
					steps={ [
						{
							key: STEP.CAMPAIGN,
							label: __(
								'Create campaign',
								'google-listings-and-ads'
							),
							content: (
								<AdsCampaign
									headerTitle={ __(
										'Create your campaign',
										'google-listings-and-ads'
									) }
									context={ eventContext }
									continueButton={ ( formContext ) => {
										return (
											<ContinueButton
												formProps={ formContext }
												onClick={ () => {
													handleContinueClick(
														STEP.ASSET_GROUP
													);
												} }
											/>
										);
									} }
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

			<EuPoliticalDeclaration />
		</>
	);
};

export default CreatePaidAdsCampaign;
