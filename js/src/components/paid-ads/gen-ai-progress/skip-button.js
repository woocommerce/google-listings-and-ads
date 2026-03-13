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
 * Component for the skip button displayed during Gen AI asset generation progress.
 *
 * This button allows users to abort the asset generation process if they choose to skip it.
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
		<AppButton onClick={ abortGenerateAssets } variant="tertiary">
			{ __( 'Skip', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default SkipButton;
