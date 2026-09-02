/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import useGoogleTagManagerConnectRedirect from './hooks/useGoogleTagManagerConnectRedirect';

/**
 * Clicking the button to grant the Google Tag Manager scope.
 *
 * @event gla_google_tag_manager_allow_access_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the account card shown when the connected Google account doesn't yet carry the
 * `tagmanager.readonly` scope — before any account/container detection runs. Clicking "Allow
 * access" redirects to Google's consent screen to grant that additional scope, then returns here.
 *
 * @fires gla_google_tag_manager_allow_access_button_click
 *
 * @return {JSX.Element} The account card.
 */
const AllowAccessGoogleTagManagerAccountCard = () => {
	const { connect: handleAllowAccessClick, loading } =
		useGoogleTagManagerConnectRedirect();

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_TAG_MANAGER }
			description={ __(
				'Google needs your permission before this store can connect to your Google Tag Manager account.',
				'google-listings-and-ads'
			) }
			alignIcon="top"
			alignIndicator="top"
			indicator={
				<AppButton
					eventName="gla_google_tag_manager_allow_access_button_click"
					eventProps={ {
						context: 'settings-tag-manager',
					} }
					onClick={ handleAllowAccessClick }
					loading={ loading }
					isSecondary
				>
					{ __( 'Allow access', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default AllowAccessGoogleTagManagerAccountCard;
