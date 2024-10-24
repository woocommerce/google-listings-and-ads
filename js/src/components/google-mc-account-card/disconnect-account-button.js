/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import { API_NAMESPACE } from '.~/data/constants';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import { useAppDispatch } from '.~/data';

/**
 * Clicking on the "connect to a different Google Merchant Center account" button.
 *
 * @event gla_mc_account_connect_different_account_button_click
 */

/**
 * Renders a button to disconnect the current connected Google Merchant Center account.
 *
 * * @fires gla_mc_account_connect_different_account_button_click
 */
const DisconnectAccountButton = ( { onDisconnect = noop } ) => {
	const { createNotice, removeNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();

	const [ fetchGoogleMCDisconnect, { loading: loadingGoogleMCDisconnect } ] =
		useApiFetchCallback( {
			path: `${ API_NAMESPACE }/mc/connection`,
			method: 'DELETE',
		} );

	/**
	 * Event handler to switch GMC account. Upon click, it will:
	 *
	 * 1. Display a notice to indicate disconnection in progress, and advise users to wait.
	 * 2. Call API to disconnect the current connected GMC account.
	 * 3. Call API to refetch list of GMC accounts.
	 * Users may have just created a new account,
	 * and we want that new account to show up in the list.
	 * 4. Call API to refetch GMC account connection status.
	 * 5. If there is an error in the above API calls, display an error notice.
	 */
	const handleSwitch = async () => {
		const { notice } = await createNotice(
			'info',
			__(
				'Disconnecting your Google Merchant Center account, please wait…',
				'google-listings-and-ads'
			)
		);

		try {
			await fetchGoogleMCDisconnect();
			invalidateResolution( 'getExistingGoogleMCAccounts', [] );
			invalidateResolution( 'getGoogleMCAccount', [] );
			onDisconnect();
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to disconnect your Google Merchant Center account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}

		removeNotice( notice.id );
	};

	return (
		<AppButton
			isLink
			disabled={ loadingGoogleMCDisconnect }
			text={ __(
				'Or, connect to a different Google Merchant Center account',
				'google-listings-and-ads'
			) }
			eventName="gla_mc_account_connect_different_account_button_click"
			onClick={ handleSwitch }
		/>
	);
};

export default DisconnectAccountButton;
