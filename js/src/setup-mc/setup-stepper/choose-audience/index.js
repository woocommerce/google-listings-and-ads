/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { Link, SearchListControl } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../../wcdl/section';
import Subsection from '../../../wcdl/subsection';
import StepContentHeader from '../components/step-content-header';
import './index.scss';

const ChooseAudience = ( props ) => {
	const { onContinue } = props;
	const [ selected, setSelected ] = useState( [] );

	// TODO: list of countries, from backend API.
	const list = [
		{
			id: 1,
			name: 'Australia',
		},
		{
			id: 2,
			name: 'United States of America',
		},
	];

	const handleListControlChange = ( items ) => {
		setSelected( items );
	};

	return (
		<div className="gla-choose-audience">
			<StepContentHeader
				step={ __( 'STEP TWO', 'google-listings-and-ads' ) }
				title={ __(
					'Choose your audience',
					'google-listings-and-ads'
				) }
				description={ __(
					'Configure who sees your product listings on Google.',
					'google-listings-and-ads'
				) }
			/>
			<Section
				title={ __( 'Audience', 'google-listings-and-ads' ) }
				description={
					<div>
						<p>
							{ __(
								'Your store must have the appropriate shipping and tax rates (if required) for customers in all your selected countries.',
								'google-listings-and-ads'
							) }
						</p>
						<p>
							<Link
								type="external"
								href="https://docs.woocommerce.com/documentation/plugins/woocommerce/getting-started/shipping/core-shipping-options/"
								target="_blank"
							>
								{ __( 'Read more', 'google-listings-and-ads' ) }
							</Link>
						</p>
					</div>
				}
			>
				<Section.Card>
					<Section.Card.Body>
						<Subsection>
							<Subsection.Title>
								{ __(
									'Site language',
									'google-listings-and-ads'
								) }
							</Subsection.Title>
							<Subsection.Body>
								{ __( 'English', 'google-listings-and-ads' ) }
							</Subsection.Body>
						</Subsection>
						<Subsection.Title>
							{ __(
								'Show my product listings to customers from these countries:',
								'google-listings-and-ads'
							) }
						</Subsection.Title>
						<SearchListControl
							messages={ {
								search: __(
									'Search for countries:',
									'google-listings-and-ads'
								),
								selected: () =>
									__(
										'Selected countries:',
										'google-listings-and-ads'
									),
							} }
							list={ list }
							selected={ selected }
							onChange={ handleListControlChange }
						></SearchListControl>
					</Section.Card.Body>
				</Section.Card>
			</Section>
			<div className="actions">
				<Button isPrimary onClick={ onContinue }>
					{ __( 'Continue', 'google-listings-and-ads' ) }
				</Button>
			</div>
		</div>
	);
};

export default ChooseAudience;
