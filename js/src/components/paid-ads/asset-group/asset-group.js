/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AppButton from '.~/components/app-button';
import AssetGroupSection from './asset-group-section';

export const ACTION_COMPLETE = 'submit-complete';
export const ACTION_SKIP_ASSETS = 'submit-with-skipping-assets';

/**
 * Renders the form container for managing an asset group of a campaign.
 *
 * Please note that this component relies on an AdaptiveForm's context, so it expects
 * a context provider component (`AdaptiveForm`) to existing in its parents.
 */
export default function AssetGroup() {
	const { isValidForm, handleSubmit, adapter } = useAdaptiveFormContext();
	const { isSubmitting, isSubmitted, submitter } = adapter;
	const currentAction = submitter?.dataset.action;

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
				<AppButton
					isTertiary
					data-action={ ACTION_SKIP_ASSETS }
					disabled={
						// TODO: Change to only validate campaign data.
						! isValidForm ||
						isSubmitted ||
						currentAction === ACTION_COMPLETE
					}
					loading={
						isSubmitting && currentAction === ACTION_SKIP_ASSETS
					}
					onClick={ handleSubmit }
				>
					{ __( 'Skip adding assets', 'google-listings-and-ads' ) }
				</AppButton>
				<AppButton
					isPrimary
					data-action={ ACTION_COMPLETE }
					disabled={
						! isValidForm ||
						isSubmitted ||
						currentAction === ACTION_SKIP_ASSETS
					}
					loading={
						isSubmitting && currentAction === ACTION_COMPLETE
					}
					onClick={ handleSubmit }
				>
					{ __( 'Launch paid campaign', 'google-listings-and-ads' ) }
				</AppButton>
			</StepContentFooter>
		</StepContent>
	);
}
