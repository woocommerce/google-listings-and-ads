/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import Section from '.~/wcdl/section';
import useWindowFocusCallbackIntervalEffect from '.~/hooks/useWindowFocusCallbackIntervalEffect';
import DisconnectAccount from '../disconnect-account';
import './index.scss';

const ClaimAccount = () => {
	const { fetchGoogleAdsAccountStatus } = useAppDispatch();
	useWindowFocusCallbackIntervalEffect( fetchGoogleAdsAccountStatus, 30 );

	return (
		<Fragment>
			<p className="gla-ads-claim-account-notice">
				{ __(
					'Claim your new Google Ads account to complete this setup.',
					'google-listings-and-ads'
				) }
			</p>

			<Section.Card.Footer>
				<DisconnectAccount />
			</Section.Card.Footer>
		</Fragment>
	);
};

export default ClaimAccount;
