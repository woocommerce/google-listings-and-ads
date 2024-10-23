/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Stepper } from '@woocommerce/components';
import { getQuery, getHistory, getNewPath } from '@woocommerce/navigation';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useLayout from '.~/hooks/useLayout';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import { useAppDispatch } from '.~/data';
import { getDashboardUrl } from '.~/utils/urls';
import convertToAssetGroupUpdateBody from '.~/components/paid-ads/convertToAssetGroupUpdateBody';
import TopBar from '.~/components/stepper/top-bar';
import HelpIconButton from '.~/components/help-icon-button';
import CampaignAssetsForm from '.~/components/paid-ads/campaign-assets-form';
import AdsCampaign from '.~/components/paid-ads/ads-campaign';
import ContinueButton from '.~/components/paid-ads/continue-button';
import AppSpinner from '.~/components/app-spinner';
import AssetGroup, {
	ACTION_SUBMIT_CAMPAIGN_AND_ASSETS,
} from '.~/components/paid-ads/asset-group';
import {
	CAMPAIGN_STEP as STEP,
	CAMPAIGN_STEP_NUMBER_MAP as STEP_NUMBER_MAP,
	CAMPAIGN_TYPE_PMAX,
} from '.~/constants';
import {
	recordStepperChangeEvent,
	recordStepContinueEvent,
} from '.~/utils/tracks';
import useFetchBudgetRecommendation from '.~/hooks/useFetchBudgetRecommendation';

const eventName = 'gla_paid_campaign_step';
const eventContext = 'edit-ads';
const dashboardURL = getDashboardUrl();
const helpButton = <HelpIconButton eventContext={ eventContext } />;

function getCurrentStep() {
	const { step } = getQuery();
	if ( Object.values( STEP ).includes( step ) ) {
		return step;
	}
	return STEP.CAMPAIGN;
}

/**
 * Renders the campaign editing page.
 *
 * @fires gla_paid_campaign_step with `{ context: 'edit-ads', triggered_by: 'step1-continue-button', action: 'go-to-step2' }`.
 * @fires gla_paid_campaign_step with `{ context: 'edit-ads', triggered_by: 'stepper-step1-button', action: 'go-to-step1' }`.
 */
const EditPaidAdsCampaign = () => {
	useLayout( 'full-content' );

	const {
		updateAdsCampaign,
		createCampaignAssetGroup,
		updateCampaignAssetGroup,
	} = useAppDispatch();

	const id = Number( getQuery().programId );
	const { loaded, data: campaigns } = useAdsCampaigns();
	const {
		hasFinishedResolution: hasResolvedAssetEntityGroups,
		invalidateResolution: invalidateResolvedAssetEntityGroups,
		data: assetEntityGroups,
	} = useAppSelectDispatch( 'getCampaignAssetGroups', id );
	const campaign = campaigns?.find( ( el ) => el.id === id );
	const assetEntityGroup = assetEntityGroups?.at( 0 );
	const { highestDailyBudget, hasFinishedResolution } =
		useFetchBudgetRecommendation( campaign?.displayCountries );
	useEffect( () => {
		if ( campaign && campaign.type !== CAMPAIGN_TYPE_PMAX ) {
			getHistory().replace( dashboardURL );
		}
	}, [ campaign ] );

	const step = getCurrentStep();
	const setStep = ( nextStep ) => {
		const url = getNewPath( { ...getQuery(), step: nextStep } );
		getHistory().push( url );
	};

	if (
		! loaded ||
		! hasResolvedAssetEntityGroups ||
		! hasFinishedResolution
	) {
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
		const { amount } = values;

		try {
			await updateAdsCampaign( campaign.id, { amount } );

			if ( action === ACTION_SUBMIT_CAMPAIGN_AND_ASSETS ) {
				let existingAssetEntityGroup = assetEntityGroup;

				if ( ! existingAssetEntityGroup ) {
					const actionPayload = await createCampaignAssetGroup( id );
					existingAssetEntityGroup = actionPayload.assetGroup;
				}

				const assetGroupId = existingAssetEntityGroup.id;
				const body = convertToAssetGroupUpdateBody(
					existingAssetEntityGroup,
					values
				);

				await updateCampaignAssetGroup( assetGroupId, body );
				invalidateResolvedAssetEntityGroups();
			}
		} catch ( e ) {
			enhancer.signalFailedSubmission();
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
				} }
				highestDailyBudget={ highestDailyBudget }
				assetEntityGroup={ assetEntityGroup }
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
									context={ eventContext }
									headerTitle={ __(
										'Edit your paid campaign',
										'google-listings-and-ads'
									) }
									continueButton={ ( formContext ) => (
										<ContinueButton
											formProps={ formContext }
											onClick={ () =>
												handleContinueClick(
													STEP.ASSET_GROUP
												)
											}
										/>
									) }
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
							content: <AssetGroup campaign={ campaign } />,
						},
					] }
				/>
			</CampaignAssetsForm>
		</>
	);
};

export default EditPaidAdsCampaign;
