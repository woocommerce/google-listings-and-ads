/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppCountryMultiSelect from '../../../components/app-country-multi-select';
import Section from '../../../wcdl/section';
import Subsection from '../../../wcdl/subsection';
import TrackedExternalLink from '../../../components/tracked-external-link';
import StepContent from '../components/step-content';
import StepContentHeader from '../components/step-content-header';
import StepContentFooter from '../components/step-content-footer';
import './index.scss';

const ChooseAudience = ( props ) => {
	const { onContinue } = props;
	const [ selected, setSelected ] = useState( [] );

	const handleListControlChange = ( items ) => {
		setSelected( items );
	};

	return (
		<div className="gla-choose-audience">
			<StepContent>
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
								<TrackedExternalLink
									id="setup-mc:audience"
									href="https://docs.woocommerce.com/documentation/plugins/woocommerce/getting-started/shipping/core-shipping-options/"
								>
									{ __(
										'Read more',
										'google-listings-and-ads'
									) }
								</TrackedExternalLink>
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
									{ __(
										'English',
										'google-listings-and-ads'
									) }
								</Subsection.Body>
							</Subsection>
							<Subsection.Title>
								{ __(
									'Show my product listings to customers from these countries:',
									'google-listings-and-ads'
								) }
							</Subsection.Title>
							<div className="input">
								<AppCountryMultiSelect
									value={ selected }
									onChange={ handleListControlChange }
								/>
							</div>
							<Subsection.HelperText>
								{ __(
									'Can’t find a country? Only supported countries are listed.',
									'google-listings-and-ads'
								) }
							</Subsection.HelperText>
						</Section.Card.Body>
					</Section.Card>
				</Section>
				<StepContentFooter>
					<Button isPrimary onClick={ onContinue }>
						{ __( 'Continue', 'google-listings-and-ads' ) }
					</Button>
				</StepContentFooter>
			</StepContent>
		</div>
	);
};

export default ChooseAudience;
