/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Tip } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import BudgetSetup from '../budget-setup';
import './index.scss';

/**
 * Renders a UI for setting up the campaign budget.
 *
 * @param {Object} props React props.
 * @param {JSX.Element} [props.children] Extra content to be rendered under the card of budget inputs.
 */
const BudgetSection = ( { children } ) => {
	return (
		<div className="gla-budget-section">
			<Section
				description={
					<p>
						{ __(
							'With Performance Max campaigns, you can set your own budget and Google’s Smart Bidding technology will serve the most appropriate ad, with the optimal bid, to maximize campaign performance. You only pay when people click on your ads, and you can start or stop your campaign whenever you want.',
							'google-listings-and-ads'
						) }
					</p>
				}
				title={ __( 'Set your budget', 'google-listings-and-ads' ) }
				verticalGap={ 4 }
			>
				<Section.Card>
					<Section.Card.Body className="gla-budget-section__card-body">
						<div>
							<Subsection.Title>
								{ __(
									'Average daily budget',
									'google-listings-and-ads'
								) }
							</Subsection.Title>
							<Subsection.Subtitle>
								{ __(
									'These values are based on your settings and the budgets of similar advertisers.',
									'google-listings-and-ads'
								) }
							</Subsection.Subtitle>
						</div>
						<BudgetSetup />
						<Tip>
							{ __(
								'We recommend running campaigns at least 1 month so it can learn to optimize for your business.',
								'google-listings-and-ads'
							) }
						</Tip>
					</Section.Card.Body>
				</Section.Card>
				{ children }
			</Section>
		</div>
	);
};

export default BudgetSection;
