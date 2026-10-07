/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import { handleApiError } from '~/utils/handleError';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import NoticeDetail from './notice-detail';
import { GOOGLE_BUSINESS_PROFILE_DESCRIPTION } from './constants';

/**
 * Clicking the button to grant the Google Business Profile scope.
 *
 * @event gla_google_business_profile_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-business-profile'.
 */

/**
 * Renders the account card shown when the connected Google Account doesn't yet carry the
 * Google Business Profile scope. "Connect" requests a connect URL granting that scope and
 * redirects the browser to it, returning here once granted.
 *
 * @fires gla_google_business_profile_connect_button_click
 *
 * @return {JSX.Element} The account card.
 */
const AllowAccessGoogleBusinessProfileAccountCard = () => {
	const { google } = useGoogleAccount();

	const fetchOption = useMemo( () => {
		return {
			path: addQueryArgs( `${ API_NAMESPACE }/business-profile/connect`, {
				login_hint: google?.email,
			} ),
		};
	}, [ google?.email ] );

	const [ fetchGoogleBusinessProfileConnect, { loading, data } ] =
		useApiFetchCallback( fetchOption );

	/**
	 * Requests a connect URL and redirects to it.
	 *
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const handleConnectClick = async () => {
		try {
			const response = await fetchGoogleBusinessProfileConnect();
			window.location.href = response.url;
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error connecting your Google Business Profile account.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_BUSINESS_PROFILE }
			description={ GOOGLE_BUSINESS_PROFILE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			indicator={
				<AppButton
					eventName="gla_google_business_profile_connect_button_click"
					eventProps={ {
						context: 'settings-business-profile',
					} }
					onClick={ handleConnectClick }
					loading={ loading || !! data }
					isSecondary
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			}
			detail={
				<NoticeDetail
					status="info"
					body={
						<p>
							{ __(
								'By connecting you are granting Google Business Profile access.',
								'google-listings-and-ads'
							) }
						</p>
					}
				/>
			}
			expandedDetail
		/>
	);
};

export default AllowAccessGoogleBusinessProfileAccountCard;
