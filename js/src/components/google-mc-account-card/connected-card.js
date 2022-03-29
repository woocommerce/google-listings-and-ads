/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import ConnectedIconLabel from '.~/components/connected-icon-label';
import Section from '.~/wcdl/section';
import { API_NAMESPACE } from '.~/data/constants';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import { useAppDispatch } from '.~/data';

/**
 * Renders a Google Merchant Center account card UI with connected account information.
 * It also provides a switch button that lets user connect with another account.
 *
 * @param {Object} props React props.
 * @param {{ id: number }} props.googleMCAccount A data payload object contains user's Google Merchant Center account ID.
 * @param {boolean} [props.hideAccountSwitch=false] Indicate whether hide the account switch block at the card footer.
 */
const ConnectedCard = ( { googleMCAccount, hideAccountSwitch = false } ) => {
	const { createNotice, removeNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();

	const [
		fetchGoogleMCDisconnect,
		{ loading: loadingGoogleMCDisconnect },
	] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/mc/connection`,
		method: 'DELETE',
	} );

	const domain = new URL( getSetting( 'homeUrl' ) ).host;

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
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ sprintf(
				// translators: 1: account domain, 2: account ID.
				__( '%1$s (%2$s)', 'google-listings-and-ads' ),
				domain,
				googleMCAccount.id
			) }
			indicator={ <ConnectedIconLabel /> }
		>
			{ ! hideAccountSwitch && (
				<Section.Card.Footer>
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
				</Section.Card.Footer>
			) }
		</AccountCard>
	);
};

export default ConnectedCard;
