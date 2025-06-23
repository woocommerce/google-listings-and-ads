/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '../app-button';
import './index.scss';

const PMaxImproveAssetsBanner = () => {
	const handleOnRemove = () => {};
	const handleOnDismiss = () => {};
	const handleOnImproveAssets = () => {};

	return (
		<Notice
			className="gla-pmax-improve-assets-banner"
			status="info"
			isDismissible={ true }
			onRemove={ handleOnRemove }
		>
			<p className="gla-pmax-improve-assets-banner__text">
				{ __(
					'Unlock more sales for your campaign, {name_of_campaign}, by focusing on improving your campaign assets.Better assets directly increase your ad strength, allowing for a wider variety of ad combinations to be shown across Google.',
					'google-listings-and-ads'
				) }
			</p>

			<div className="gla-pmax-improve-assets-banner__actions">
				<AppButton onClick={ handleOnImproveAssets } isSecondary>
					{ __( 'Improve Assets', 'google-listings-and-ads' ) }
				</AppButton>

				<AppButton isTertiary onClick={ handleOnDismiss }>
					{ __( 'Dismiss', 'google-listings-and-ads' ) }
				</AppButton>
			</div>
		</Notice>
	);
};

export default PMaxImproveAssetsBanner;
