/**
 * External dependencies
 */
import {
	Flex,
	FlexBlock,
	FlexItem,
	Notice,
	SelectControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import googleLogoURL from '~/images/logo/google-g-logo.svg';
import { SYNC_STATUS_HAS_ERRORS, SYNC_STATUS_SYNCED } from './constants';

const {
	channelVisibility: {
		field_id: fieldId,
		channel_visibility: channelVisibility,
		product_is_visible: productIsVisible,
		sync_status: syncStatus = null,
		issues = [],
		options = {},
	} = {},
} = glaData || {};

/**
 * Channel Visibility Settings component.
 *
 * The component will allow the user to select the channel visibility for the product and
 * will be displayed when the ads setup is complete.
 *
 * @return {JSX.Element} The Channel Visibility Settings component
 */
const ChannelVisibilitySettings = () => {
	const [ channelVisibilityValue, setChannelVisibilityValue ] = useState(
		productIsVisible ? channelVisibility : 'dont-sync-and-show'
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
		channelVisibilityValue === 'sync-and-show' &&
		syncStatus !== SYNC_STATUS_SYNCED;

	const hasIssues = issues.length > 0;

	/**
	 * Parse the options object into an array of options.
	 * Options is an object with the following structure:
	 * {
	 *   'sync-and-show': 'Sync and show',
	 *   'dont-sync-and-show': "Don't sync and show",
	 * }
	 *
	 * @return {Array<{label: string, value: string}>}
	 */
	const selectOptions = Object.entries( options ).map(
		( [ value, label ] ) => ( { label, value } )
	);

	return (
		<Flex direction="column" gap={ 4 } className="gla-channel-visibility">
			<FlexBlock>
				<Flex gap={ 2 } align="center" justify="flex-start">
					<FlexItem>
						<Flex
							className="gla-channel-visibility__label"
							align="center"
							gap={ 2 }
							justify="flex-start"
						>
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

					{ selectOptions.length > 0 && (
						<FlexBlock>
							<SelectControl
								name={ fieldId }
								options={ selectOptions }
								value={ channelVisibilityValue }
								onChange={ ( value ) =>
									setChannelVisibilityValue( value )
								}
								disabled={ ! productIsVisible }
								__nextHasNoMarginBottom
							/>
						</FlexBlock>
					) }
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
