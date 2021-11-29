/**
 * External dependencies
 */
import { Icon, warning as warningIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppSpinner from '.~/components/app-spinner';
import AppButton from '.~/components/app-button';
import ConnectedIconLabel from '.~/components/connected-icon-label';
import LoadingLabel from '.~/components/loading-label';
import wpLogoURL from '../setup-mc/setup-stepper/setup-accounts/wordpressdotcom-account/wpcom-account-card/wp-logo.svg';

const wpLogo = <img src={ wpLogoURL } width="40" height="40" alt="" />;
const style = {
	display: 'flex',
	flexDirection: 'column',
	gap: '24px',
	width: '680px',
	margin: '0 auto 24px',
};

// For temporary demo.

export default function AccountCardsDemo() {
	return (
		<div style={ style }>
			<div>
				Fully custom AccountCard appearance from the consumer side
				<AccountCard
					icon={ wpLogo }
					title="WordPress.com"
					description="user.somebo@example.com"
					indicator={ <ConnectedIconLabel /> }
				/>
			</div>
			<div>
				The original usage has not changed
				<AccountCard
					appearance={ APPEARANCE.GOOGLE }
					description="user.somebo@example.com"
					helper="This Google account is connected to your store’s product feed."
					indicator={ <LoadingLabel text="Connecting…" /> }
				/>
			</div>
			<div>
				Use default description
				<AccountCard
					appearance={ APPEARANCE.GOOGLE_ADS }
					indicator={
						<AppButton isSecondary text="Allow full access" />
					}
				/>
			</div>
			<div>
				Specify description
				<AccountCard
					appearance={ APPEARANCE.GOOGLE_ADS }
					description="Account 123456789"
					indicator={
						<AppButton isSecondary text="Switch account" />
					}
				/>
			</div>
			<div>
				Warning of contact info without icon
				<AccountCard
					className="gla-contact-info-preview-card"
					icon={ null }
					title={
						<>
							<Icon
								className="gla-contact-info-preview-card__notice-icon"
								icon={ warningIcon }
								size={ 24 }
							/>
							Please add your store address
						</>
					}
					helper="Google requires the store address for all stores using Google Merchant Center."
					indicator={ <AppButton isSecondary text="Edit" /> }
				/>
			</div>
			<div>
				Show loading spinner by empty appearance
				<AccountCard description={ <AppSpinner /> } />
			</div>
		</div>
	);
}
