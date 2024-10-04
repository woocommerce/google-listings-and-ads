/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';

/**
 *
 * @param {Object} props Props.
 * @param {boolean} props.isLoading Whether the footer is in a loading state.
 * @param {Function} props.onCreateNew Callback to handle the new ads account creation.
 * @return {JSX.Element} Footer component.
 */
const ConnectAdsFooter = ( { isLoading, onCreateNew = () => {} } ) => (
	<AppButton isTertiary disabled={ isLoading } onClick={ onCreateNew }>
		{ __(
			'Or, create a new Google Ads account',
			'google-listings-and-ads'
		) }
	</AppButton>
);

export default ConnectAdsFooter;
