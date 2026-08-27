/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import AppButton from '~/components/app-button';

/**
 * Triggered when the skip button is clicked during Gen AI asset generation progress.
 *
 * @event gla_gen_ai_progress_skip_button_click
 */

/**
 * Component for the skip button displayed during Gen AI asset generation progress.
 *
 * This button allows users to abort the asset generation process if they choose to skip it.
 *
 * @fires gla_gen_ai_progress_skip_button_click when the skip button is clicked.
 *
 * @return {JSX.Element|null} The SkipButton component, or null if not currently generating assets.
 */
const SkipButton = () => {
	const { adapter } = useAdaptiveFormContext();
	const { abortGenerateAssets, isGeneratingAssets } = adapter;

	if ( ! isGeneratingAssets ) {
		return null;
	}

	return (
		<AppButton
			eventName="gla_gen_ai_progress_skip_button_click"
			onClick={ abortGenerateAssets }
			variant="tertiary"
		>
			{ __( 'Skip', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default SkipButton;
