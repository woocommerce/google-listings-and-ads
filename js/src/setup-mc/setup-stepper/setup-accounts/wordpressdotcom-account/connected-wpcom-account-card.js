/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import getConnectedJetpackInfo from '.~/utils/getConnectedJetpackInfo';
import WPComAccountCard from './wpcom-account-card';

const ConnectedWPComAccountCard = ( { jetpack } ) => {
	return (
		<WPComAccountCard
			description={ getConnectedJetpackInfo( jetpack ) }
			indicator={
				<div>
					TODO: { __( 'Connected', 'google-listings-and-ads' ) }
				</div>
			}
		/>
	);
};

export default ConnectedWPComAccountCard;
