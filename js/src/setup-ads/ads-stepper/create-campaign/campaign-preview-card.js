/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';
import GridiconChevronLeft from 'gridicons/dist/chevron-left';
import GridiconChevronRight from 'gridicons/dist/chevron-right';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppButton from '.~/components/app-button';
import CampaignPreview from '.~/components/paid-ads/campaign-preview';
import './campaign-preview-card.scss';

/**
 * @typedef { import(".~/components/paid-ads/campaign-preview/campaign-preview.js").CampaignPreviewHandler } CampaignPreviewHandler
 */

/**
 * Renders a Card that includes a CampaignPreview with previous and next buttons.
 */
export default function CampaignPreviewCard() {
	/**
	 * @type {import('react').MutableRefObject<CampaignPreviewHandler>}
	 */
	const previewRef = useRef();

	const handleClick = ( e ) => {
		const step = Number( e.currentTarget.dataset.step );
		previewRef.current.moveBy( step );
	};

	return (
		<Section.Card className="gla-campaign-preview-card">
			<Section.Card.Body>
				<Flex align="start" gap={ 9 }>
					<FlexBlock>
						<Section.Card.Title>
							{ __(
								'Preview product ad',
								'google-listings-and-ads'
							) }
						</Section.Card.Title>
						<div>
							{ __(
								`Each of your product variants will have its own ad. Previews shown here are examples and don't include all possible formats.`,
								'google-listings-and-ads'
							) }
						</div>
					</FlexBlock>
					<FlexItem>
						<Flex align="center" gap={ 5 }>
							<AppButton
								className="gla-campaign-preview-card__moving-button"
								icon={ <GridiconChevronLeft /> }
								iconSize={ 16 }
								onClick={ handleClick }
								data-step="-1"
							/>
							<CampaignPreview
								ref={ previewRef }
								autoplay={ false }
							/>
							<AppButton
								className="gla-campaign-preview-card__moving-button"
								icon={ <GridiconChevronRight /> }
								iconSize={ 16 }
								onClick={ handleClick }
								data-step="1"
							/>
						</Flex>
					</FlexItem>
				</Flex>
			</Section.Card.Body>
		</Section.Card>
	);
}
