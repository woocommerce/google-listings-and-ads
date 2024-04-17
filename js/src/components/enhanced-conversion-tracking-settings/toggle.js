/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useCallback,
	Fragment,
	createInterpolateElement,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import AppSpinner from '.~/components/app-spinner';
import AppStandaloneToggleControl from '.~/components/app-standalone-toggle-control';
import TrackableLink from '.~/components/trackable-link';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import useGoogleAdsEnhancedConversionSettingsURL from '.~/hooks/useGoogleAdsEnhancedConversionSettingsURL';

const TOGGLE_LABEL_MAP = {
	[ ENHANCED_ADS_CONVERSION_STATUS.DISABLED ]: __(
		'Enable',
		'google-listings-and-ads'
	),
	[ ENHANCED_ADS_CONVERSION_STATUS.ENABLED ]: __(
		'Disable',
		'google-listings-and-ads'
	),
};

const Toggle = () => {
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();
	const { allowEnhancedConversions, hasFinishedResolution } =
		useAllowEnhancedConversions();
	const url = useGoogleAdsEnhancedConversionSettingsURL();

	const handleOnChange = useCallback(
		( value ) => {
			updateEnhancedAdsConversionStatus(
				value
					? ENHANCED_ADS_CONVERSION_STATUS.ENABLED
					: ENHANCED_ADS_CONVERSION_STATUS.DISABLED
			);
		},
		[ updateEnhancedAdsConversionStatus ]
	);

	if ( ! hasFinishedResolution ) {
		return <AppSpinner />;
	}

	return (
		<Fragment>
			<p>
				{ createInterpolateElement(
					__(
						'To properly measure your enhanced conversions, please ensure Enhanced Conversions is enabled in <link>Google Ads Settings</link>, and that the setup method chosen is Google Tag',
						'google-listings-and-ads'
					),
					{
						link: (
							<TrackableLink
								target="_blank"
								type="external"
								href={ url }
							/>
						),
					}
				) }
			</p>

			<AppStandaloneToggleControl
				checked={
					allowEnhancedConversions ===
					ENHANCED_ADS_CONVERSION_STATUS.ENABLED
				}
				disabled={ ! hasFinishedResolution }
				onChange={ handleOnChange }
				label={
					TOGGLE_LABEL_MAP[ allowEnhancedConversions ] ||
					__( 'Enable', 'google-listings-and-ads' )
				}
			/>
		</Fragment>
	);
};

export default Toggle;
