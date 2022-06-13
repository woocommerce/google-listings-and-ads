/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useUnmountableNotice from '.~/hooks/useUnmountableNotice';

/**
 * Hook to create a CES notice
 *
 * @param {string} label CES prompt label.
 * @param {JSX.Element} icon Icon (React component) to be shown on the notice.
 * @param {Function} [onClickCallBack] Function to call when Give feedback is clicked.
 * @param {Function} [onNoticeDismissedCallback] Function to call when the notice is dismissed.
 *
 * @return {Function} a function that will create the CES notice.
 */
const useCESNotice = (
	label,
	icon,
	onClickCallBack = noop,
	onNoticeDismissedCallback = noop
) => {
	return useUnmountableNotice( 'success', label, {
		actions: [
			{
				label: __( 'Give feedback', 'google-listings-and-ads' ),
				onClick: onClickCallBack,
			},
		],
		icon,
		explicitDismiss: true,
		onDismiss: onNoticeDismissedCallback,
	} );
};

export default useCESNotice;
