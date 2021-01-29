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

const DisconnectAccount = () => {
	const [ loading, setLoading ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();

	const handleDisconnectClick = async () => {
		setLoading( true );

		try {
			await apiFetch( {
				path: '/wc/gla/jetpack/connect',
				method: 'DELETE',
			} );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to disconnect your WordPress.com account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		} finally {
			setLoading( false );
		}
	};

	return (
		<TitleButtonLayout
			title={ __( 'Connected', 'google-listings-and-ads' ) }
			button={
				<AppButton
					isTertiary
					isDestructive
					loading={ loading }
					onClick={ handleDisconnectClick }
				>
					{ __( 'Disconnect', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default DisconnectAccount;
