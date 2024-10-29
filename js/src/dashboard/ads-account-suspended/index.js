/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { Icon, external as externalIcon, warning as warningIcon } from '@wordpress/icons';

/**
 * Internal dependencies
*/
import './index.scss';

/**
 * Renders a Notice component with extra props.
 *
 * ## Usage
 *
 * ```jsx
 * <AdsAccountSuspended >
 * 		My Notice
 * </AdsAccountSuspended>
 * ```
 */
const AdsAccountSuspended = () => {
	const [ isShown, setIsShown ] = useState( true );
	const isSuspended = true;
	if ( ! isSuspended ) {
		return null;
	}

	const getActionLabel = () => (
		<>
			{ __(
				'Resolve issues.',
				'google-listings-and-ads'
			) }
			<Icon
				className="gla-get-started-notice__icon"
				icon={ externalIcon }
				size={ 18 }
			/>
		</>
	);

	return ( isShown &&
		<Notice
			className="gla-ads-suspended-notice"
			status="error"
			onDismiss={ () => setIsShown( false ) }
			actions={[
				{
				  label: getActionLabel(),
				  onClick: 'https://ads.google.com/aw/overview?euid=0',
				  className: 'gla-ads-suspended-notice__action',
				},
			]}
		>
			<p className="gla-ads-suspended-notice__message">
				<Icon
					icon={ warningIcon }
					size={ 24 }
					className="gla-ads-suspended-notice__icon"
				/>
				{ __(
					'Your Google Ads account has been suspended. Your campaigns are not running.',
					'google-listings-and-ads'
				) }
			</p>
		</Notice>
	);
};

export default AdsAccountSuspended;
