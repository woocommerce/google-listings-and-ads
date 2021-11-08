/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Tip } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppTooltip from '.~/components/app-tooltip';
import SectionContent from './section-content';
import './index.scss';

const GoogleMCAccount = ( props ) => {
	const { disabled = false } = props;

	return (
		<div className="gla-google-mc-account">
			<Section
				description={
					<Tip>
						{ createInterpolateElement(
							__(
								'Google’s Shopping tab has an average of <strong>over 50% increase in clicks and over 100% increase in impressions</strong> across free listings and ads. <tooltip>Source</tooltip>',
								'google-listings-and-ads'
							),
							{
								strong: <strong></strong>,
								tooltip: (
									<AppTooltip
										text={ __(
											'Google Internal Data, July 2020, based on an A/B test comparing performance for users seeing the updated layout on the Shopping property vs a control group not seeing the new experience.',
											'google-listings-and-ads'
										) }
									></AppTooltip>
								),
							}
						) }
					</Tip>
				}
			>
				<SectionContent disabled={ disabled } />
			</Section>
		</div>
	);
};

export default GoogleMCAccount;
