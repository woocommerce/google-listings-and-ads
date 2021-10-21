/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import AppDocumentationLink from '.~/components/app-documentation-link';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

const conversionMap = {
	link: (
		<AppDocumentationLink
			context="setup-mc-accounts"
			linkId="required-google-permissions"
			href="https://docs.woocommerce.com/document/google-listings-and-ads/#required-google-permissions"
		/>
	),
};

export default function AuthGoogleAccountCard( { disabled = false } ) {
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchGoogleConnect, { loading, data } ] = useApiFetchCallback( {
		path: '/wc/gla/google/connect',
	} );

	const handleConnectClick = async () => {
		try {
			const { url } = await fetchGoogleConnect();
			window.location.href = url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your Google account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	const description = (
		<>
			{ __(
				'Required to sync with Google Merchant Center and Google Ads.',
				'google-listings-and-ads'
			) }
			<p>
				<em>
					{ createInterpolateElement(
						__(
							'You will be prompted to give WooCommerce access to your Google account. Please check all the checkboxes to give WooCommerce all required permissions. <link>Read more</link>',
							'google-listings-and-ads'
						),
						conversionMap
					) }
				</em>
			</p>
		</>
	);

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			disabled={ disabled }
			hideIcon
			alignIcon="top"
			description={ description }
			indicator={
				<AppButton
					isSecondary
					disabled={ disabled }
					loading={ loading || data }
					eventName="gla_google_account_connect_button_click"
					text={ __( 'Connect', 'google-listings-and-ads' ) }
					onClick={ handleConnectClick }
				/>
			}
		/>
	);
}
