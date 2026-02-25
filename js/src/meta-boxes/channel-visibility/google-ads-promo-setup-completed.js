/**
 * External dependencies
 */
import { Flex, FlexItem, SelectControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import googleLogoURL from '~/images/logo/gogole-g-logo.svg';

const { channelVisibility: { channel_visibility } = {} } = glaData || {};

const GoogleAdsPromoSetupCompleted = () => {
	const [ channelVisibility, setChannelVisibility ] =
		useState( channel_visibility );

	return (
		<Flex
			gap={ 2 }
			align="center"
			justify="space-between"
			className="gla-channel-visibility"
		>
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
				<SelectControl
					name="gla_channel_visibility"
					options={ [
						{
							label: __(
								'Sync and show',
								'google-listings-and-ads'
							),
							value: 'sync-and-show',
						},
						{
							label: __(
								"Don't sync and show",
								'google-listings-and-ads'
							),
							value: 'dont-sync-and-show',
						},
					] }
					value={ channelVisibility }
					onChange={ ( value ) => setChannelVisibility( value ) }
					__nextHasNoMarginBottom
				/>
			</FlexItem>
		</Flex>
	);
};

export default GoogleAdsPromoSetupCompleted;
