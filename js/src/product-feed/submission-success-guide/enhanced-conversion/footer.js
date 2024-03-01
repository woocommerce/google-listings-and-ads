/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, Fragment } from '@wordpress/element';
import noop from 'lodash/noop';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import AppButton from '.~/components/app-button';
import CTA from '.~/components/enhanced-conversion-tracking-settings/cta';

const Footer = ( { onClose = noop } ) => {
	const { createNotice } = useDispatchCoreNotices();

	const handleEnableOrDisableClick = useCallback( () => {
		createNotice(
			'info',
			__( 'Status successfully set', 'google-listings-and-ads' )
		);

		onClose();
	}, [ createNotice, onClose ] );

	return (
		<Fragment>
			<div className="gla-submission-success-guide__space_holder" />

			<AppButton
				isSecondary
				// @todo: review data-action label
				data-action="view-product-feed"
				onClick={ onClose }
			>
				{ __( 'Close', 'google-listings-and-ads' ) }
			</AppButton>

			<CTA
				onEnableClick={ handleEnableOrDisableClick }
				onDisableClick={ handleEnableOrDisableClick }
				acceptTermsLabel={ __(
					'Sign terms of service on Google Ads',
					'google-listings-and-ads'
				) }
				enableLabel={ __( 'Confirm', 'google-listings-and-ads' ) }
			/>
		</Fragment>
	);
};

export default Footer;
