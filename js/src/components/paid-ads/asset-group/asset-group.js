/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ASSET_FORM_KEY } from '.~/constants';
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AppButton from '.~/components/app-button';
import AssetGroupSection from './asset-group-section';

export const ACTION_SUBMIT_CAMPAIGN_AND_ASSETS = 'submit-campaign-and-assets';
export const ACTION_SUBMIT_CAMPAIGN_ONLY = 'submit-campaign-only';

/**
 * @typedef {import('.~/data/actions').Campaign} Campaign
 */

/**
 * Renders the container of the form content for managing an asset group of a campaign.
 *
 * Please note that this component relies on an AdaptiveForm's context, so it expects
 * a context provider component (`AdaptiveForm`) to existing in its parents.
 *
 * @param {Object} props React props.
 * @param {Campaign} [props.campaign] Campaign data to be edited. If not provided, this component will show campaign creation UI.
 */
export default function AssetGroup( { campaign } ) {
	const isCreation = ! campaign;
	// TODO: When editing, it needs to distinguish whether the given asset group is empty. Will be implemented later.
	const isEmptyAssetGroup = true;
	const { isValidForm, handleSubmit, adapter } = useAdaptiveFormContext();
	const { isValidAssetGroup, isSubmitting, isSubmitted, submitter } = adapter;
	const currentAction = submitter?.dataset.action;

	const handleLaunchClick = ( ...args ) => {
		if ( isValidAssetGroup ) {
			handleSubmit( ...args );
		} else {
			adapter.showValidation();
		}
	};

	return (
		<StepContent>
			<StepContentHeader
				title={ __( 'Boost your campaign', 'google-listings-and-ads' ) }
				description={ __(
					'Get more conversions by adding creative assets to your campaign',
					'google-listings-and-ads'
				) }
			/>
			<AssetGroupSection />
			<StepContentFooter>
				{ ( isCreation || isEmptyAssetGroup ) && (
					<AppButton
						isTertiary
						data-action={ ACTION_SUBMIT_CAMPAIGN_ONLY }
						disabled={
							! isValidForm ||
							isSubmitted ||
							currentAction === ACTION_SUBMIT_CAMPAIGN_AND_ASSETS
						}
						loading={
							isSubmitting &&
							currentAction === ACTION_SUBMIT_CAMPAIGN_ONLY
						}
						onClick={ handleSubmit }
					>
						{ __(
							'Skip adding assets',
							'google-listings-and-ads'
						) }
					</AppButton>
				) }
				<AppButton
					isPrimary
					data-action={ ACTION_SUBMIT_CAMPAIGN_AND_ASSETS }
					disabled={
						! adapter.baseAssetGroup[ ASSET_FORM_KEY.FINAL_URL ] ||
						isSubmitted ||
						currentAction === ACTION_SUBMIT_CAMPAIGN_ONLY
					}
					loading={
						isSubmitting &&
						currentAction === ACTION_SUBMIT_CAMPAIGN_AND_ASSETS
					}
					onClick={ handleLaunchClick }
				>
					{ isCreation
						? __(
								'Launch paid campaign',
								'google-listings-and-ads'
						  )
						: __( 'Save changes', 'google-listings-and-ads' ) }
				</AppButton>
			</StepContentFooter>
		</StepContent>
	);
}
