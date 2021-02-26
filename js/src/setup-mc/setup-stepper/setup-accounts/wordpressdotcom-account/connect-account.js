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
	const [ fetchJetpackConnect, { loading, data } ] = useApiFetch( {
		path: '/wc/gla/jetpack/connect',
	} );

	const handleConnectClick = async () => {
		try {
			const d = await fetchJetpackConnect();
			window.location.href = d.url;
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
