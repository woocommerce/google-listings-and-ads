/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import wpLogoURL from './wp-logo.svg';

const WPComAccountCard = () => {
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchJetpackConnect, { loading, data } ] = useApiFetchCallback( {
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
		<AccountCard
			appearance={ {
				icon: (
					<img
						src={ wpLogoURL }
						alt={ __(
							'WordPress.com Logo',
							'google-listings-and-ads'
						) }
						width="40"
						height="40"
					/>
				),
				title: 'WordPress.com',
			} }
			description={ __(
				'Required to connect with Google',
				'google-listings-and-ads'
			) }
			indicator={
				<AppButton
					isSecondary
					loading={ loading || data }
					eventName="gla_wordpress_account_connect_button_click"
					onClick={ handleConnectClick }
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default WPComAccountCard;
