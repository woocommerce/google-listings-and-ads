/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { CREATED_NOT_CONNECTED_MESSAGE } from '../constants';
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { handleApiError } from '~/utils/handleError';
import AppButton from '~/components/app-button';
import NoticeDetail from '../notice-detail';
import useRefreshGoogleTagManagerConnection from '../hooks/useRefreshGoogleTagManagerConnection';
import './connect-container.scss';

/**
 * Renders the notice shown while a container was created but couldn't be connected, in place of the
 * way to connect one. "Try again" selects the pending container, so no second container is created.
 *
 * @param {Object} props React props.
 * @param {string} props.containerId The ID of the container that was created but not connected.
 *
 * @return {JSX.Element} The notice.
 */
export default function PendingContainerNotice( { containerId } ) {
	const { refreshConnection, loading: isRefreshing } =
		useRefreshGoogleTagManagerConnection();
	const [ fetchConnectContainer, { loading: isConnecting } ] =
		useApiFetchCallback( {
			path: `${ API_NAMESPACE }/tag-manager/containers`,
			method: 'POST',
			data: {
				id: containerId,
			},
		} );

	const handleTryAgainClick = async () => {
		try {
			await fetchConnectContainer();
			await refreshConnection();
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error connecting your Google Tag Manager container.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<div className="gla-google-tag-manager-account-card__connect-container-notice">
			<NoticeDetail
				status="error"
				body={ <p>{ CREATED_NOT_CONNECTED_MESSAGE }</p> }
				actions={ [
					<AppButton
						key="try-again"
						loading={ isConnecting || isRefreshing }
						onClick={ handleTryAgainClick }
						isLink
					>
						{ __( 'Try again', 'google-listings-and-ads' ) }
					</AppButton>,
				] }
			/>
		</div>
	);
}
