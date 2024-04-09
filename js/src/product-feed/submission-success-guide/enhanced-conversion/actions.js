/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, Fragment } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import AppButton from '.~/components/app-button';
import CTA from '.~/components/enhanced-conversion-tracking-settings/cta';

const Actions = ( { onModalClose = noop } ) => {
	const { createNotice } = useDispatchCoreNotices();

	const handleEnableOrDisableClick = useCallback( () => {
		createNotice(
			'info',
			__( 'Status successfully set', 'google-listings-and-ads' )
		);

		onModalClose();
	}, [ createNotice, onModalClose ] );

	return (
		<Fragment>
			<div className="gla-submission-success-guide__space_holder" />

			<AppButton isSecondary data-action="close" onClick={ onModalClose }>
				{ __( 'Close', 'google-listings-and-ads' ) }
			</AppButton>

			<CTA
				onEnableClick={ handleEnableOrDisableClick }
				onDisableClick={ handleEnableOrDisableClick }
				enableLabel={ __( 'Confirm', 'google-listings-and-ads' ) }
			/>
		</Fragment>
	);
};

export default Actions;
