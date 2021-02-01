/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '../../../../wcdl/section';
import DisabledDiv from '../../../../components/disabled-div';
import AppButton from '../../../../components/app-button';
import useDispatchCoreNotices from '../../../../hooks/useDispatchCoreNotices';
import TitleButtonLayout from '../title-button-layout';

const GoogleAccount = ( props ) => {
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
		<DisabledDiv disabled={ disabled }>
			<Section
				title={ __( 'Google account', 'google-listings-and-ads' ) }
				description={ __(
					'WooCommerce uses your Google account to sync with Google Merchant Center and Google Ads.',
					'google-listings-and-ads'
				) }
			>
				<Section.Card>
					<Section.Card.Body>
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
									{ __(
										'Connect',
										'google-listings-and-ads'
									) }
								</AppButton>
							}
						/>
					</Section.Card.Body>
				</Section.Card>
			</Section>
		</DisabledDiv>
	);
};

export default GoogleAccount;
