/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

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
 * Clicking on the submit button on the campaign creation or editing page.
 * If a value is recorded as `unknown`, it's because no assets are imported and therefore unknown.
 *
 * @event gla_submit_campaign_button_click
 * @property {string} context Indicate the place where the button is located. Possible values: `campaign-creation`, `campaign-editing`.
 * @property {string} action Indicate which submit button is clicked. Possible values: `submit-campaign-and-assets`, `submit-campaign-only`.
 * @property {string} audiences Country codes of the campaign audience countries, e.g. `US,JP,AU`.
 * @property {string} budget Daily average cost of the campaign.
 * @property {string} assets_validation Whether all asset values are valid or at least one invalid. Possible values: `valid`, `invalid`, `unknown`.
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
 */

/**
 * Renders the container of the form content for managing an asset group of a campaign.
 *
 * Please note that this component relies on an AdaptiveForm's context, so it expects
 * a context provider component (`AdaptiveForm`) to existing in its parents.
 *
 * @param {Object} props React props.
 * @param {Campaign} [props.campaign] Campaign data to be edited. If not provided, this component will show campaign creation UI.
 *
 * @fires gla_submit_campaign_button_click
 */
export default function AssetGroup( { campaign } ) {
	const isCreation = ! campaign;
	const {
		isValidForm,
		handleSubmit,
		adapter,
		values,
	} = useAdaptiveFormContext();
	const { isValidAssetGroup, isSubmitting, isSubmitted, submitter } = adapter;
	const currentAction = submitter?.dataset.action;

	function recordSubmissionClickEvent( event ) {
		const finalUrl = values[ ASSET_FORM_KEY.FINAL_URL ];
		const eventProps = {
			context: isCreation ? 'campaign-creation' : 'campaign-editing',
			action: event.target.dataset.action,
			audiences: values.countryCodes.join( ',' ),
			budget: values.amount.toString(),
			assets_validation: isValidAssetGroup ? 'valid' : 'invalid',
		};

		if ( ! finalUrl ) {
			eventProps.assets_validation = 'unknown';
		}

		Object.values( ASSET_FORM_KEY ).forEach( ( key ) => {
			const name = `number_of_${ key }`;
			const num = [ values[ key ] ].flat().filter( Boolean ).length;
			eventProps[ name ] = finalUrl ? num.toString() : 'unknown';
		} );

		recordEvent( 'gla_submit_campaign_button_click', eventProps );
	}

	const handleSkipClick = ( event ) => {
		handleSubmit( event );
		recordSubmissionClickEvent( event );
	};

	const handleLaunchClick = ( event ) => {
		if ( isValidAssetGroup ) {
			handleSubmit( event );
		} else {
			adapter.showValidation();
		}
		recordSubmissionClickEvent( event );
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
				{ ( isCreation || adapter.isEmptyAssetEntityGroup ) && (
					// Currently, the PMax Assets feature in this extension doesn't offer the function
					// to delete the asset entity group, so it needs to hide the skip button if the editing
					// asset group is not considered empty.
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
						onClick={ handleSkipClick }
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
