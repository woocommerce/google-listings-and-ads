/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';

const glaDateTimeFormat =
	glaData.dateFormat +
	( glaData.dateFormat.trim() && glaData.timeFormat.trim() ? ', ' : '' ) +
	glaData.timeFormat;

export default glaDateTimeFormat;
