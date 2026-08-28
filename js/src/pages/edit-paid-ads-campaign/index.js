/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Stepper } from '@woocommerce/components';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { useEffect, useState } from '@wordpress/element';
import { isEqual } from 'lodash';
/**
 * Internal dependencies
 */
import useLayout from '~/hooks/useLayout';
import useQuery from '~/hooks/useQuery';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import { useAppDispatch } from '~/data';
import { getDashboardUrl } from '~/utils/urls';
import convertToAssetGroupUpdateBody, {
	diffAssetOperations,
} from '~/components/paid-ads/convertToAssetGroupUpdateBody';
import TopBar from '~/components/stepper/top-bar';
import HelpIconButton from '~/components/help-icon-button';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import ContinueButton from '~/components/paid-ads/continue-button';
import AppSpinner from '~/components/app-spinner';
import AssetGroup, {
	ACTION_SUBMIT_CAMPAIGN_AND_ASSETS,
} from '~/components/paid-ads/asset-group';
import {
	CAMPAIGN_STEP as STEP,
	CAMPAIGN_STEP_NUMBER_MAP as STEP_NUMBER_MAP,
	CAMPAIGN_TYPE_PMAX,
} from '~/constants';
import {
	recordStepperChangeEvent,
	recordStepContinueEvent,
} from '~/utils/tracks';
import useNavigateAwayPromptEffect from '~/hooks/useNavigateAwayPromptEffect';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import EuPoliticalDeclaration from '~/components/eu-political-declaration';
import useEuPoliticalDeclarationContext from '~/hooks/useEuPoliticalDeclarationContext';

const eventName = 'gla_paid_campaign_step';
const eventContext = 'edit-ads';
const dashboardURL = getDashboardUrl();
const helpButton = <HelpIconButton eventContext={ eventContext } />;

function getCurrentStep( step ) {
	if ( Object.values( STEP ).includes( step ) ) {
		return step;
	}
	return STEP.CAMPAIGN;
}

function isNotOurStep( location ) {
	const allowList = new Set( [
		getNewPath( { step: STEP.CAMPAIGN } ),
		getNewPath( { step: STEP.ASSET_GROUP } ),
	] );
	const destination = location.pathname + location.search;
	return ! allowList.has( destination );
}

/**
 * Renders the campaign editing page.
 *
 * @fires gla_paid_campaign_step with `{ context: 'edit-ads', triggered_by: 'step1-continue-button', action: 'go-to-step2' }`.
 * @fires gla_paid_campaign_step with `{ context: 'edit-ads', triggered_by: 'stepper-step1-button', action: 'go-to-step1' }`.
 */
const EditPaidAdsCampaign = () => {
	useLayout( 'full-content' );
	const [ didChange, setDidChange ] = useState( false );
	const [ isSubmit, setIsSubmit ] = useState( false );
	const { handleError: handleEuPoliticalDeclarationError } =
		useEuPoliticalDeclarationContext();
	const {
		updateAdsCampaign,
		createCampaignAssetGroup,
		updateCampaignAssetGroup,
	} = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	const query = useQuery();
	const id = Number( query.programId );

	const { loaded, data: campaigns } = useAdsCampaigns();
	const {
		hasFinishedResolution: hasResolvedAssetEntityGroups,
		invalidateResolution: invalidateResolvedAssetEntityGroups,
		data: assetEntityGroups,
	} = useAppSelectDispatch( 'getCampaignAssetGroups', id );
	const campaign = campaigns?.find( ( el ) => el.id === id );
	const assetEntityGroup = assetEntityGroups?.at( 0 );

	useEffect( () => {
		if ( campaign && campaign.type !== CAMPAIGN_TYPE_PMAX ) {
			getHistory().replace( dashboardURL );
		}
	}, [ campaign ] );

	const step = getCurrentStep( query.step );

	useNavigateAwayPromptEffect(
		__(
			'You have unsaved campaign data. Are you sure you want to leave?',
			'google-listings-and-ads'
		),
		didChange && ! isSubmit,
		isNotOurStep
	);

	const setStep = ( nextStep ) => {
		const url = getNewPath( { ...query, step: nextStep } );
		getHistory().push( url );
	};

	if ( ! loaded || ! hasResolvedAssetEntityGroups ) {
		return (
			<>
				<TopBar
					backHref={ dashboardURL }
					helpButton={ helpButton }
					title={ __( 'Loading…', 'google-listings-and-ads' ) }
				/>
				<AppSpinner />
			</>
		);
	}

	if ( ! campaign ) {
		return (
			<>
				<TopBar
					backHref={ dashboardURL }
					helpButton={ helpButton }
					title={ __( 'Edit Campaign', 'google-listings-and-ads' ) }
				/>
				<div>
					{ __(
						'Error in loading your ads campaign. Please try again later.',
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

	const handleOnChange = ( value, allValues ) => {
		const isAmountChanged = allValues.dailyBudget !== campaign.amount;

		const isDisplayUrlPathChanged =
			!! assetEntityGroup &&
			! isEqual(
				assetEntityGroup.display_url_path,
				allValues.display_url_path
			);

		const hasAssetOperations =
			!! assetEntityGroup &&
			diffAssetOperations( assetEntityGroup, allValues ).length > 0;

		const hasChange =
			isAmountChanged || isDisplayUrlPathChanged || hasAssetOperations;

		setDidChange( hasChange );
	};

	const handleSubmit = async ( values, enhancer ) => {
		const { action } = enhancer.submitter.dataset;
		const {
			dailyBudget: amount,
			hasConfirmedEuPoliticalContent:
				eu_political_advertising_confirmation,
		} = values;
		setIsSubmit( true );
		try {
			await updateAdsCampaign( campaign.id, {
				amount,
				eu_political_advertising_confirmation,
			} );

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

				createNotice(
					'success',
					__(
						'You’ve successfully updated your campaign!',
						'google-listings-and-ads'
					)
				);
			}
		} catch ( e ) {
			handleEuPoliticalDeclarationError( e );
			setIsSubmit( false );
			enhancer.signalFailedSubmission();
			return;
		}

		getHistory().push( getDashboardUrl() );
	};

	return (
		<>
			<TopBar
				backHref={ dashboardURL }
				helpButton={ helpButton }
				title={ sprintf(
					// translators: %s: campaign's name.
					__( 'Edit %s', 'google-listings-and-ads' ),
					campaign.name
				) }
			/>
			<CampaignAssetsForm
				assetEntityGroup={ assetEntityGroup }
				countryCodes={ campaign.displayCountries }
				initialCampaign={ {
					level: 'current',
					id: campaign.id,
					currentAmount: campaign.amount,
					hasConfirmedEuPoliticalContent:
						campaign.eu_political_advertising_confirmation,
				} }
				onChange={ handleOnChange }
				onSubmit={ handleSubmit }
			>
				<Stepper
					currentStep={ step }
					steps={ [
						{
							key: STEP.CAMPAIGN,
							label: __(
								'Edit campaign',
								'google-listings-and-ads'
							),
							content: (
								<AdsCampaign
									context={ eventContext }
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
									headerTitle={ __(
										'Edit your campaign',
										'google-listings-and-ads'
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

			<EuPoliticalDeclaration />
		</>
	);
};

export default EditPaidAdsCampaign;
