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
import Section from '../../../../wcdl/section';
import TitleButtonLayout from '../title-button-layout';
import useDispatchCoreNotices from '../../../../hooks/useDispatchCoreNotices';

const WordPressDotComAccount = () => {
	const [ loading, setLoading ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();

	const handleConnectClick = async () => {
		setLoading( true );

		try {
			const { url } = await apiFetch( {
				path: '/wc/gla/jetpack/connect',
			} );
			window.location.href = url;
		} catch ( error ) {
			setLoading( false );
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
		<Section
			title={ __( 'WordPress.com', 'google-listings-and-ads' ) }
			description={ __(
				'WooCommerce requires a WordPress.com account to connect to Google.',
				'google-listings-and-ads'
			) }
		>
			<Section.Card>
				<Section.Card.Body>
					<TitleButtonLayout
						title={ __(
							'Connect your WordPress.com account',
							'google-listings-and-ads'
						) }
						button={
							<AppButton
								isSecondary
								loading={ loading }
								onClick={ handleConnectClick }
							>
								{ __( 'Connect', 'google-listings-and-ads' ) }
							</AppButton>
						}
					/>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default WordPressDotComAccount;
