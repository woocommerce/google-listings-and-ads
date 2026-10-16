/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { CREATED_NOT_CONNECTED_MESSAGE } from '../constants';
import AppButton from '~/components/app-button';
import NoticeDetail from '../components/notice-detail';
import useConnectGoogleTagManagerContainer from '../hooks/useConnectGoogleTagManagerContainer';

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
	const [ connectContainer, { loading: isConnecting } ] =
		useConnectGoogleTagManagerContainer();

	const handleTryAgainClick = () => {
		return connectContainer( containerId );
	};

	return (
		<div className="gla-google-tag-manager-account-card__notice-wrapper">
			<NoticeDetail
				status="error"
				body={ <p>{ CREATED_NOT_CONNECTED_MESSAGE }</p> }
				actions={ [
					<AppButton
						key="try-again"
						loading={ isConnecting }
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
