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
import googleLogoURL from '~/images/logo/gogole-g-logo.svg';

const {
	channelVisibility: {
		field_id,
		channel_visibility,
		product_is_visible,
		issues = [],
		options = [],
	} = {},
} = glaData || {};

const GoogleAdsPromoSetupCompleted = () => {
	const [ channelVisibility, setChannelVisibility ] = useState(
		product_is_visible ? channel_visibility : 'dont-sync-and-show'
	);
	let productIssues = issues;

	if ( ! product_is_visible ) {
		productIssues = [
			...issues,
			__(
				'This product cannot be shown on any channel because it is hidden from your store catalog.',
				'google-listings-and-ads'
			),
		];
	}

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
					{ selectOptions.length > 0 && (
						<FlexItem>
							<SelectControl
								name={ field_id }
								options={ selectOptions }
								value={ channelVisibility }
								onChange={ ( value ) =>
									setChannelVisibility( value )
								}
								disabled={ ! product_is_visible }
								__nextHasNoMarginBottom
							/>
						</FlexItem>
					) }
				</Flex>
			</FlexBlock>

			{ productIssues?.length > 0 && (
				<FlexBlock>
					<Notice status="warning" isDismissible={ false }>
						<p>
							<strong>
								{ __( 'Issues', 'google-listings-and-ads' ) }
							</strong>
						</p>
						<ul>
							{ productIssues.map( ( issue ) => (
								<li key={ issue }>{ issue }</li>
							) ) }
						</ul>
					</Notice>
				</FlexBlock>
			) }
		</Flex>
	);
};

export default GoogleAdsPromoSetupCompleted;
