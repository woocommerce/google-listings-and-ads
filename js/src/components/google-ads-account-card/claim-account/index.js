/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import DisconnectAccount from '../disconnect-account';
import './index.scss';

const ClaimAccount = () => {
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
