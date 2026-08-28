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
import Section from '~/components/section';
import AppButton from '~/components/app-button';
import CampaignPreview from './campaign-preview';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import './campaign-preview-card.scss';

/**
 * @typedef { import("./campaign-preview.js").CampaignPreviewHandler } CampaignPreviewHandler
 */

/**
 * Renders a Card that includes a CampaignPreview with previous and next buttons.
 */
export default function CampaignPreviewCard() {
	const { hasGoogleMCConnection } = useGoogleMCAccount();

	/**
	 * @type {import('react').MutableRefObject<CampaignPreviewHandler>}
	 */
	const previewRef = useRef();

	const handleClick = ( e ) => {
		const step = Number( e.currentTarget.dataset.step );
		previewRef.current.moveBy( step );
	};

	const content = hasGoogleMCConnection
		? {
				title: __( 'Preview product ad', 'google-listings-and-ads' ),
				description: __(
					"Each of your product variants will have its own ad. Previews shown here are examples and don't include all possible formats.",
					'google-listings-and-ads'
				),
		  }
		: {
				title: __( 'Ad Preview', 'google-listings-and-ads' ),
				description: __(
					"Previews shown here are examples and don't include all possible formats.",
					'google-listings-and-ads'
				),
		  };
	const { title, description } = content;

	return (
		<Section.Card className="gla-campaign-preview-card">
			<Section.Card.Body>
				<Flex align="start" direction={ [ 'column', 'row' ] } gap={ 9 }>
					<FlexBlock>
						<Section.Card.Title>{ title }</Section.Card.Title>
						<div>{ description }</div>
					</FlexBlock>
					<FlexItem>
						<Flex align="center" gap={ 5 }>
							<AppButton
								className="gla-campaign-preview-card__moving-button"
								data-step="-1"
								icon={ <GridiconChevronLeft /> }
								iconSize={ 16 }
								onClick={ handleClick }
							/>
							<CampaignPreview
								autoplay={ false }
								ref={ previewRef }
							/>
							<AppButton
								className="gla-campaign-preview-card__moving-button"
								data-step="1"
								icon={ <GridiconChevronRight /> }
								iconSize={ 16 }
								onClick={ handleClick }
							/>
						</Flex>
					</FlexItem>
				</Flex>
			</Section.Card.Body>
		</Section.Card>
	);
}
