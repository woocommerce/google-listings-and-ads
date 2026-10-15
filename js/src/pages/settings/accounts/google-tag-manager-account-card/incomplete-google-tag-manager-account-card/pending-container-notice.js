/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import { handleApiError } from '~/utils/handleError';
import AccountCardTextDetail from '../../account-card-text-detail';
import AppButton from '~/components/app-button';
import AccountNameWithLink from '../account-name-with-link';
import AdsConversionDuplicateNotice from '../ads-conversion-duplicate-notice';
import NoticeDetail from '../notice-detail';
import useRefreshGoogleTagManagerConnection from '../hooks/useRefreshGoogleTagManagerConnection';
import './connect-container.scss';

export const CREATED_NOT_CONNECTED_MESSAGE = __(
	"The container was created, but its connection couldn't be confirmed. Please try again.",
	'google-listings-and-ads'
);

/**
 * Renders the account card's detail while a container was created but couldn't be connected, in
 * place of the container selection. "Try again" asks the backend to connect that container; it
 * connects the pending one instead of creating another, so no name is needed.
 *
 * @return {JSX.Element} The detail.
 */
export default function PendingContainerNotice() {
	const { account } = useGoogleTagManagerAccount();
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
		<Flex direction="column" gap={ 4 }>
			<FlexItem>
				<AccountCardTextDetail>
					<AccountNameWithLink account={ account } />
				</AccountCardTextDetail>
			</FlexItem>
			<FlexItem>
				<AdsConversionDuplicateNotice />
			</FlexItem>
			<FlexItem className="gla-google-tag-manager-account-card__connect-container-notice">
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
			</FlexItem>
		</Flex>
	);
}
