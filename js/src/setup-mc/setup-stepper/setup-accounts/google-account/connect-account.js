/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '../../../../components/app-button';
import TitleButtonLayout from '../title-button-layout';
import useDispatchCoreNotices from '../../../../hooks/useDispatchCoreNotices';

const ConnectAccount = ( props ) => {
	const { disabled = false } = props;
	const [ loading, setLoading ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();

	const handleConnectClick = async () => {
		setLoading( true );

		try {
			const { url } = await apiFetch( {
				path: '/wc/gla/google/connect',
			} );
			window.location.href = url;
		} catch ( error ) {
			setLoading( false );
			createNotice(
				'error',
				__(
					'Unable to connect your Google account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<TitleButtonLayout
			title={ __(
				'Connect your Google account',
				'google-listings-and-ads'
			) }
			button={
				<AppButton
					isSecondary
					disabled={ disabled }
					loading={ loading }
					onClick={ handleConnectClick }
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default ConnectAccount;
