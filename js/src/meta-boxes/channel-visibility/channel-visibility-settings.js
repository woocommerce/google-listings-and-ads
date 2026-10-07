/**
 * External dependencies
 */
import {
	Flex,
	FlexBlock,
	FlexItem,
	FormToggle,
	Notice,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import googleLogoURL from '~/images/logo/google-g-logo.svg';
import {
	SYNC_STATUS_HAS_ERRORS,
	SYNC_STATUS_SYNCED,
	CHANNEL_VISIBILITY_SYNC_AND_SHOW,
	CHANNEL_VISIBILITY_DONT_SYNC_AND_SHOW,
} from './constants';

/**
 * Channel Visibility Settings component.
 *
 * The component will allow the user to select the channel visibility for the product and
 * will be displayed when the ads setup is complete.
 *
 * @return {JSX.Element} The Channel Visibility Settings component
 */
const ChannelVisibilitySettings = () => {
	const {
		channelVisibility: {
			field_id: fieldId,
			channel_visibility: channelVisibility,
			product_is_visible: productIsVisible,
			sync_status: syncStatus = null,
			issues = [],
		} = {},
	} = glaData || {};

	// PHP treats a product without a saved visibility as `sync-and-show`.
	const [ channelVisibilityValue, setChannelVisibilityValue ] = useState(
		productIsVisible
			? channelVisibility ?? CHANNEL_VISIBILITY_SYNC_AND_SHOW
			: CHANNEL_VISIBILITY_DONT_SYNC_AND_SHOW
	);

	let syncStatusText = null;

	if ( syncStatus === SYNC_STATUS_HAS_ERRORS ) {
		syncStatusText = __( 'Issues detected', 'google-listings-and-ads' );
	} else if ( syncStatus ) {
		// Capitalize the first letter and replace dashes with spaces (e.g. 'not-synced' → 'Not synced').
		syncStatusText =
			syncStatus.charAt( 0 ).toUpperCase() +
			syncStatus.slice( 1 ).replace( '-', ' ' );
	}

	const shouldDisplaySyncNotice =
		productIsVisible &&
		syncStatus &&
		channelVisibilityValue === CHANNEL_VISIBILITY_SYNC_AND_SHOW &&
		syncStatus !== SYNC_STATUS_SYNCED;

	const hasIssues = issues.length > 0;

	return (
		<Flex
			direction="column"
			gap={ 4 }
			className="gla-channel-visibility gla-channel-visibility--settings"
		>
			<FlexBlock>
				<Flex gap={ 2 } align="center" justify="space-between">
					<FlexItem>
						<Flex gap={ 2 } align="center">
							<FlexItem>
								<img
									className="gla-channel-visibility__logo"
									src={ googleLogoURL }
									alt={ __(
										'Google Logo',
										'google-listings-and-ads'
									) }
									width={ 16 }
									height={ 16 }
								/>
							</FlexItem>
							<FlexItem>
								{ __( 'Google', 'google-listings-and-ads' ) }
							</FlexItem>
						</Flex>
					</FlexItem>

					<FlexItem>
						{ /*
						 * Unchecked checkboxes don't submit, so this fallback posts the OFF
						 * value. It must come before the toggle: PHP keeps the last value for
						 * a repeated field name, so a checked toggle overrides it.
						 */ }
						<input
							type="hidden"
							name={ fieldId }
							value={ CHANNEL_VISIBILITY_DONT_SYNC_AND_SHOW }
							disabled={ ! productIsVisible }
						/>
						<FormToggle
							name={ fieldId }
							value={ CHANNEL_VISIBILITY_SYNC_AND_SHOW }
							checked={
								channelVisibilityValue ===
								CHANNEL_VISIBILITY_SYNC_AND_SHOW
							}
							onChange={ ( event ) =>
								setChannelVisibilityValue(
									event.target.checked
										? CHANNEL_VISIBILITY_SYNC_AND_SHOW
										: CHANNEL_VISIBILITY_DONT_SYNC_AND_SHOW
								)
							}
							disabled={ ! productIsVisible }
							aria-label={ __(
								'Show product on Google',
								'google-listings-and-ads'
							) }
						/>
					</FlexItem>
				</Flex>
			</FlexBlock>

			{ ! productIsVisible && (
				<FlexBlock>
					<Notice status="info" isDismissible={ false }>
						<p>
							{ __(
								'This product cannot be shown on any channel because it is hidden from your store catalog.',
								'google-listings-and-ads'
							) }
						</p>
					</Notice>
				</FlexBlock>
			) }

			{ shouldDisplaySyncNotice && syncStatusText && (
				<FlexBlock>
					<Notice
						className="gla-channel-visibility__sync-notice"
						isDismissible={ false }
						status={ hasIssues ? 'warning' : 'info' }
					>
						<p>
							<strong>
								{ __(
									'Google sync status',
									'google-listings-and-ads'
								) }
							</strong>
						</p>
						<p className="gla-channel-visibility__sync-status">
							{ syncStatusText }
						</p>

						{ hasIssues && (
							<>
								<p>
									<strong>
										{ __(
											'Issues',
											'google-listings-and-ads'
										) }
									</strong>
								</p>
								<ul>
									{ issues.map( ( issue ) => (
										<li key={ issue }>{ issue }</li>
									) ) }
								</ul>
							</>
						) }
					</Notice>
				</FlexBlock>
			) }
		</Flex>
	);
};

export default ChannelVisibilitySettings;
