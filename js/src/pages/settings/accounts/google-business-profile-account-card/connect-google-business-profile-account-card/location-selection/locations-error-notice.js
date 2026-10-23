/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import NoticeDetail from '../../notice-detail';

/**
 * Renders the notice shown when the Google Business Profile locations couldn't be loaded, with a
 * "Try again" refetch.
 *
 * @return {JSX.Element} The notice.
 */
export default function LocationsErrorNotice() {
	const { fetchGoogleBusinessProfileLocations } = useAppDispatch();
	const [ isRefreshing, setIsRefreshing ] = useState( false );

	const handleTryAgainClick = async () => {
		setIsRefreshing( true );
		await fetchGoogleBusinessProfileLocations();
		setIsRefreshing( false );
	};

	return (
		<NoticeDetail
			status="error"
			body={
				<p>
					{ __(
						"We couldn't load your Google Business Profile locations.",
						'google-listings-and-ads'
					) }
				</p>
			}
			actions={ [
				<AppButton
					key="try-again"
					onClick={ handleTryAgainClick }
					disabled={ isRefreshing }
					loading={ isRefreshing }
					isSecondary
				>
					{ __( 'Try again', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		/>
	);
}
