/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import AppButton from '.~/components/app-button';
import CTA from '.~/components/enhanced-conversion-tracking-settings/cta';

const EnhancedConversionFooter = ( { handleGuideFinish } ) => {
	const { createNotice } = useDispatchCoreNotices();

	const handleEnableOrDisableClick = useCallback( () => {
		createNotice(
			'info',
			__( 'Status successfully set', 'google-listings-and-ads' )
		);

		handleGuideFinish?.();
	}, [ createNotice, handleGuideFinish ] );

	return (
		<>
			<div className="gla-submission-success-guide__space_holder" />

			<CTA
				onEnableClick={ handleEnableOrDisableClick }
				onDisableClick={ handleEnableOrDisableClick }
				acceptTermsLabel={ __(
					'Sign terms of service on Google Ads',
					'google-listings-and-ads'
				) }
				enableLabel={ __( 'Confirm', 'google-listings-and-ads' ) }
			/>

			<AppButton
				isSecondary
				// @todo: review data-action label
				data-action="view-product-feed"
				onClick={ handleGuideFinish }
			>
				{ __( 'Close', 'google-listings-and-ads' ) }
			</AppButton>
		</>
	);
};

export default EnhancedConversionFooter;
