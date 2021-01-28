/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import AppButton from '../../../../components/app-button';
import Section from '../../../../wcdl/section';
import TitleButtonLayout from '../title-button-layout';
import useDispatchCoreNotices from '../../../../hooks/useDispatchCoreNotices';
import { STORE_KEY } from '../../../../data/constants';
import AppSpinner from '../../../../components/app-spinner';

const WordPressDotComAccount = () => {
	const [ loading, setLoading ] = useState( false );
	const { jetpack, isResolving } = useSelect( ( select ) => {
		const acc = select( STORE_KEY ).getJetpackAccount();
		const resolving = select( STORE_KEY ).isResolving(
			'getJetpackAccount'
		);

		return { jetpack: acc, isResolving: resolving };
	} );
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
					{ isResolving && <AppSpinner /> }
					{ ! isResolving && ! jetpack?.active && (
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
									{ __(
										'Connect',
										'google-listings-and-ads'
									) }
								</AppButton>
							}
						/>
					) }
					{ ! isResolving && jetpack?.active && (
						<TitleButtonLayout
							title={ __(
								'Connected',
								'google-listings-and-ads'
							) }
							button={
								<AppButton
									isTertiary
									isDestructive
									onClick={ handleConnectClick }
								>
									{ __(
										'Disconnect',
										'google-listings-and-ads'
									) }
								</AppButton>
							}
						/>
					) }
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default WordPressDotComAccount;
