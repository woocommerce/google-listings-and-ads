/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { handleApiError } from '~/utils/handleError';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';

/**
 * Clicking the button to grant the Google Tag Manager scope.
 *
 * @event gla_google_tag_manager_allow_access_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the account card shown when the connected Google account doesn't yet carry the
 * `tagmanager.readonly` scope — before any account/container detection runs. Clicking "Allow
 * access" requests a fresh connect URL granting that additional scope and redirects the browser
 * to it, returning here once granted.
 *
 * @fires gla_google_tag_manager_allow_access_button_click
 *
 * @return {JSX.Element} The account card.
 */
const AllowAccessGoogleTagManagerAccountCard = () => {
	const [ fetchGoogleTagManagerConnect, { loading, data } ] =
		useApiFetchCallback( {
			path: `${ API_NAMESPACE }/tag-manager/connect`,
		} );

	/**
	 * Handles the "Allow access" button click: requests a connect URL and redirects to it.
	 *
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const handleAllowAccessClick = async () => {
		try {
			const response = await fetchGoogleTagManagerConnect();
			window.location.href = response.url;
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error connecting your Google Tag Manager account.',
					'google-listings-and-ads'
				)
			);
		}
	};

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
					loading={ loading || !! data }
					isSecondary
				>
					{ __( 'Allow access', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default AllowAccessGoogleTagManagerAccountCard;
