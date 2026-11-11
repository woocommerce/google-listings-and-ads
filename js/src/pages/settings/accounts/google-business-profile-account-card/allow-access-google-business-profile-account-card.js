/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { useEffect, useState } from '@wordpress/element';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { GOOGLE_SERVICE, GOOGLE_SERVICE_OAUTH_PARAM } from '~/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import { handleApiError } from '~/utils/handleError';
import { redirectTo } from '~/utils/urls';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import NoticeDetail from './notice-detail';
import {
	GOOGLE_BUSINESS_PROFILE_DESCRIPTION,
	GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT,
} from './constants';

/**
 * Clicking the button to grant the Google Business Profile scope.
 *
 * @event gla_google_business_profile_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-business-profile'.
 */

/**
 * Renders the card's notice: what "Connect" grants, or that access wasn't granted on a return
 * from Google.
 *
 * @param {Object} props Component props.
 * @param {boolean} props.isAccessNotGranted Whether the merchant came back from Google without granting access.
 * @return {JSX.Element} The notice.
 */
const AccessNoticeDetail = ( { isAccessNotGranted } ) => {
	if ( isAccessNotGranted ) {
		return (
			<NoticeDetail
				status="error"
				title={ __(
					"Google Business Profile access wasn't granted",
					'google-listings-and-ads'
				) }
				body={
					<p>
						{ __(
							'Click Connect to try again, and allow access when Google asks.',
							'google-listings-and-ads'
						) }
					</p>
				}
			/>
		);
	}

	return (
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
	);
};

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

	// Kept from the first render, since removing the query arg below re-renders the page without it.
	const [ isAccessNotGranted ] = useState( () => {
		return (
			getQuery()?.[ GOOGLE_SERVICE_OAUTH_PARAM ] ===
			GOOGLE_SERVICE.BUSINESS_PROFILE
		);
	} );

	useEffect( () => {
		if ( ! isAccessNotGranted ) {
			return;
		}

		getHistory().replace(
			getNewPath( {
				[ GOOGLE_SERVICE_OAUTH_PARAM ]: undefined,
			} )
		);
	}, [ isAccessNotGranted ] );

	const [ fetchGoogleBusinessProfileConnect, { loading, data } ] =
		useApiFetchCallback( {
			path: addQueryArgs( `${ API_NAMESPACE }/business-profile/connect`, {
				login_hint: google?.email,
			} ),
		} );

	/**
	 * Requests a connect URL and redirects to it.
	 *
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const handleConnectClick = async () => {
		try {
			const response = await fetchGoogleBusinessProfileConnect();
			redirectTo( response.url );
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
						context: GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT,
					} }
					onClick={ handleConnectClick }
					loading={ loading || !! data }
					isSecondary
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			}
			detail={
				<AccessNoticeDetail isAccessNotGranted={ isAccessNotGranted } />
			}
			expandedDetail
		/>
	);
};

export default AllowAccessGoogleBusinessProfileAccountCard;
