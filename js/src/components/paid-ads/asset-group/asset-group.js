/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { ASSET_FORM_KEY } from '~/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import StepContent from '~/components/stepper/step-content';
import StepContentHeader from '~/components/stepper/step-content-header';
import StepContentFooter from '~/components/stepper/step-content-footer';
import StepContentActions from '~/components/stepper/step-content-actions';
import AppButton from '~/components/app-button';
import Faqs from './faqs';
import { recordGlaEvent, CONTEXT_ADS_ONLY_ONBOARDING } from '~/utils/tracks';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import AssetGroupHeader from './asset-group-header';
import AssetGroupEditor from './asset-group-editor';
import { upsertActionedCampaign } from '~/utils/actionedCampaignsCache';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import './asset-group.scss';

export const ACTION_SUBMIT_CAMPAIGN_AND_ASSETS = 'submit-campaign-and-assets';
export const ACTION_SUBMIT_CAMPAIGN_ONLY = 'submit-campaign-only';

/**
 * @typedef {import('~/data/actions').Campaign} Campaign
 */

/**
 * Clicking on the submit button on the campaign creation or editing page.
 * If a value is recorded as `unknown`, it's because no assets are imported and therefore unknown.
 *
 * @event gla_submit_campaign_button_click
 * @property {string} context Indicate the place where the button is located. Possible values: `campaign-creation`, `campaign-editing`.
 * @property {string} action Indicate which submit button is clicked. Possible values: `submit-campaign-and-assets`, `submit-campaign-only`.
 * @property {string} audiences Country codes of the campaign audience countries, e.g. `US,JP,AU`.
 * @property {string} budget Daily average cost of the campaign.
 * @property {string} assets_validation Whether all asset values are valid or at least one invalid. Possible values: `valid`, `invalid`, `unknown`.
 * @property {string} campaign_id The ID of the campaign being created or edited, or `new` if it's a new campaign.
 * @property {string} number_of_business_name The number of this asset in string type or `unknown`.
 * @property {string} number_of_marketing_image Same as above.
 * @property {string} number_of_square_marketing_image Same as above.
 * @property {string} number_of_portrait_marketing_image Same as above.
 * @property {string} number_of_logo Same as above.
 * @property {string} number_of_headline Same as above.
 * @property {string} number_of_long_headline Same as above.
 * @property {string} number_of_description Same as above.
 * @property {string} number_of_call_to_action_selection Same as above.
 * @property {string} number_of_final_url Same as above.
 * @property {string} number_of_display_url_path Same as above.
 * @property {string} number_of_youtube_videos Same as above.
 * @property {boolean} has_raise_budget_recommendation Whether there is a budget recommendation that suggests raising the budget.
 * @property {string} level The budget recommendation level selected by the user. Possible values: `low`, `current`, `recommended`, `high`, or `custom`.
 */

/**
 * Renders the container of the form content for managing an asset group of a campaign.
 *
 * Please note that this component relies on an AdaptiveForm's context, so it expects
 * a context provider component (`AdaptiveForm`) to existing in its parents.
 *
 * @param {Object} props React props.
 * @param {Campaign} [props.campaign] Campaign data to be edited. If not provided, this component will show campaign creation UI.
 * @param {string} [props.context] The context where this component is used.
 * @param {Function} [props.onSkipClick=noop] Callback function to be called when the skip button is clicked.
 *
 * @fires gla_submit_campaign_button_click
 */
export default function AssetGroup( {
	campaign,
	context,
	onSkipClick = noop,
} ) {
	const isCreation = ! campaign;
	const { isValidForm, handleSubmit, adapter, values } =
		useAdaptiveFormContext();
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const {
		isValidAssetGroup,
		isSubmitting,
		isSubmitted,
		submitter,
		isFetchingAssets,
	} = adapter;
	const { hasGoogleMCConnection } = useGoogleMCAccount();
	const currentAction = submitter?.dataset.action;

	const hasRaiseBudgetRecommendation = () => {
		if (
			[ 'high', 'recommended', 'low' ].includes( values.level ) &&
			! isCreation
		) {
			return (
				adapter.budgetRecommendation[ values.level ].metrics?.uplift &&
				Number(
					adapter.budgetRecommendation[ values.level ].metrics?.uplift
				) !== 0
			);
		}

		return false;
	};

	function recordSubmissionClickEvent( event ) {
		const audiences = isCreation ? countryCodes : campaign.displayCountries;
		const finalUrl = values[ ASSET_FORM_KEY.FINAL_URL ];
		const eventProps = {
			context: isCreation ? 'campaign-creation' : 'campaign-editing',
			action: event.target.dataset.action,
			audiences: audiences.join( ',' ),
			budget: values.dailyBudget.toString(),
			assets_validation: isValidAssetGroup ? 'valid' : 'invalid',
			campaign_id: isCreation ? 'new' : campaign.id,
			level: values.level,
		};

		if ( ! finalUrl ) {
			eventProps.assets_validation = 'unknown';
		}

		eventProps.has_raise_budget_recommendation =
			hasRaiseBudgetRecommendation();

		Object.values( ASSET_FORM_KEY ).forEach( ( key ) => {
			const name = `number_of_${ key }`;
			const num = [ values[ key ] ].flat().filter( Boolean ).length;
			eventProps[ name ] = finalUrl ? num.toString() : 'unknown';
		} );

		recordGlaEvent( 'gla_submit_campaign_button_click', eventProps );
	}

	const recordActionedCampaign = () => {
		if ( hasRaiseBudgetRecommendation() ) {
			upsertActionedCampaign( campaign.id );
		}
	};

	const handleSkipClick = async ( event ) => {
		if ( context !== CONTEXT_ADS_ONLY_ONBOARDING ) {
			handleSubmit( event );
		}
		recordActionedCampaign();
		recordSubmissionClickEvent( event );

		onSkipClick();
	};

	const handleLaunchClick = ( event ) => {
		if ( isValidAssetGroup ) {
			handleSubmit( event );
			recordActionedCampaign();
		} else {
			adapter.showValidation();
		}
		recordSubmissionClickEvent( event );
	};

	return (
		<StepContent>
			<StepContentHeader
				description={ __(
					'Drive greater performance by adding text, image, and video assets to create more personalized and engaging ads.',
					'google-listings-and-ads'
				) }
				title={ __(
					'Optimize your campaign',
					'google-listings-and-ads'
				) }
			/>

			<AssetGroupHeader />

			{ ! isFetchingAssets && (
				<>
					<AssetGroupEditor />

					<StepContentFooter>
						<StepContentActions>
							{ ( isCreation ||
								adapter.isEmptyAssetEntityGroup ) &&
								hasGoogleMCConnection && (
									// Currently, the PMax Assets feature in this extension doesn't offer the function
									// to delete the asset entity group, so it needs to hide the skip button if the editing
									// asset group is not considered empty.
									<AppButton
										data-action={
											ACTION_SUBMIT_CAMPAIGN_ONLY
										}
										disabled={
											! isValidForm ||
											isSubmitted ||
											currentAction ===
												ACTION_SUBMIT_CAMPAIGN_AND_ASSETS
										}
										loading={
											isSubmitting &&
											currentAction ===
												ACTION_SUBMIT_CAMPAIGN_ONLY
										}
										onClick={ handleSkipClick }
										isTertiary
									>
										{ __(
											'Skip this step',
											'google-listings-and-ads'
										) }
									</AppButton>
								) }
							<AppButton
								data-action={
									ACTION_SUBMIT_CAMPAIGN_AND_ASSETS
								}
								disabled={
									! adapter.baseAssetGroup[
										ASSET_FORM_KEY.FINAL_URL
									] ||
									isSubmitted ||
									currentAction ===
										ACTION_SUBMIT_CAMPAIGN_ONLY
								}
								loading={
									isSubmitting &&
									currentAction ===
										ACTION_SUBMIT_CAMPAIGN_AND_ASSETS
								}
								onClick={ handleLaunchClick }
								isPrimary
							>
								{ isCreation
									? __(
											'Create campaign',
											'google-listings-and-ads'
									  )
									: __(
											'Save changes',
											'google-listings-and-ads'
									  ) }
							</AppButton>
						</StepContentActions>
						<Faqs />
					</StepContentFooter>
				</>
			) }
		</StepContent>
	);
}
