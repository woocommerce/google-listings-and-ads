/**
 * Internal dependencies
 */
import { isFeatureEnabled } from '~/utils/isFeatureEnabled';

const useFeature = ( feature ) => {
	return isFeatureEnabled( feature );
};

export default useFeature;
