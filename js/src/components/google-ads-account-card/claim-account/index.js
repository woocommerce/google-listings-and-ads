/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import Section from '~/components/section';
import useWindowFocusCallbackIntervalEffect from '~/hooks/useWindowFocusCallbackIntervalEffect';
import DisconnectAccount from '../disconnect-account';
import './index.scss';

const ClaimAccount = () => {
	const { fetchGoogleAdsAccountStatus } = useAppDispatch();
	useWindowFocusCallbackIntervalEffect( fetchGoogleAdsAccountStatus, 30 );

	return (
		<>
			<p className="gla-ads-claim-account-notice">
				{ __(
					'Claim your new Google Ads account to complete this setup.',
					'google-listings-and-ads'
				) }
			</p>

			<Section.Card.Footer>
				<DisconnectAccount />
			</Section.Card.Footer>
		</>
	);
};

export default ClaimAccount;
