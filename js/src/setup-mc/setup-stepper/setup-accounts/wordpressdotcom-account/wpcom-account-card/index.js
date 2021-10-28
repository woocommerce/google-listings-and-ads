/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard from '.~/components/account-card';
import wpLogoURL from './wp-logo.svg';

const WPComAccountCard = ( props ) => {
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
			{ ...props }
		/>
	);
};

export default WPComAccountCard;
