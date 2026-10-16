/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { handleApiError } from '~/utils/handleError';
import AppButton from '~/components/app-button';
import NoticeDetail from '../notice-detail';
import useRefreshGoogleTagManagerConnection from '../hooks/useRefreshGoogleTagManagerConnection';
import './connect-container.scss';

export const CREATED_NOT_CONNECTED_MESSAGE = __(
	"The container was created, but its connection couldn't be confirmed. Please try again.",
	'google-listings-and-ads'
);

/**
 * Renders the notice shown while a container was created but couldn't be connected, in place of the
 * way to connect one. "Try again" asks the backend to connect that container; it
 * connects the pending one instead of creating another, so no name is needed.
 *
 * @return {JSX.Element} The notice.
 */
export default function PendingContainerNotice() {
	const { refreshConnection } = useRefreshGoogleTagManagerConnection();
	const [ isRetrying, setIsRetrying ] = useState( false );
	const [ fetchConnectContainer ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/tag-manager/containers`,
		method: 'POST',
	} );

	const handleTryAgainClick = async () => {
		setIsRetrying( true );
		try {
			await fetchConnectContainer().catch( ( connectError ) => {
				// A container is already connected, so only the card has to catch up with it.
				if ( connectError?.data?.reason !== 'already_connected' ) {
					throw connectError;
				}
			} );
			await refreshConnection();
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error connecting your Google Tag Manager container.',
					'google-listings-and-ads'
				)
			);
		} finally {
			setIsRetrying( false );
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
						loading={ isRetrying }
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
