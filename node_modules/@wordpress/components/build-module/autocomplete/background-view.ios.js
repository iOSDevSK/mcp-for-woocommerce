/**
 * External dependencies
 */
import { BlurView } from '@react-native-community/blur';

/**
 * Internal dependencies
 */
import styles from './style.scss';
import { jsx as _jsx } from "react/jsx-runtime";
const BackgroundView = ({
  children
}) => {
  return /*#__PURE__*/_jsx(BlurView, {
    style: styles['components-autocomplete__background-blur'],
    blurType: "prominent",
    blurAmount: 10,
    children: children
  });
};
export default BackgroundView;
//# sourceMappingURL=background-view.ios.js.map