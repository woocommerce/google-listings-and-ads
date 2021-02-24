/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import TitleButtonLayout from '../title-button-layout';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useApiFetch from '.~/hooks/useApiFetch';

const ConnectAccount = () => {
	const { createNotice } = useDispatchCoreNotices();
	const [ apiFetch, { loading, data } ] = useApiFetch();

	const handleConnectClick = async () => {
		try {
			const { url } = await apiFetch( {
				path: '/wc/gla/jetpack/connect',
			} );
			window.location.href = url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your WordPress.com account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<TitleButtonLayout
			title={ __(
				'Connect your WordPress.com account',
				'google-listings-and-ads'
			) }
			button={
				<AppButton
					isSecondary
					loading={ loading || data }
					onClick={ handleConnectClick }
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default ConnectAccount;
