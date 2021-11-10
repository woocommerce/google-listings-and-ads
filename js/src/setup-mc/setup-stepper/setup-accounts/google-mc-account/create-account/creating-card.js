/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import AccountCard, { APPEARANCE } from '.~/components/account-card';

const CreatingCard = ( props ) => {
	const { retryAfter, onRetry = () => {} } = props;

	useEffect( () => {
		if ( ! retryAfter ) {
			return;
		}

		const intervalID = setInterval( () => {
			onRetry();
		}, retryAfter * 1000 );

		return () => clearInterval( intervalID );
	}, [ retryAfter, onRetry ] );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ __(
				'This may take a few minutes, please wait a moment…',
				'google-listings-and-ads'
			) }
			indicator={
				<AppButton loading>
					{ __( 'Creating…', 'google-listings-and-ads' ) }
				</AppButton>
			}
		></AccountCard>
	);
};

export default CreatingCard;
