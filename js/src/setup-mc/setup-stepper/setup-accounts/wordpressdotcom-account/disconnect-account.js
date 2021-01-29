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

const DisconnectAccount = ( props ) => {
	const { jetpack } = props;
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
			title={ jetpack.email }
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
