/**
 * External dependencies
 */
import { View } from 'react-native';

/**
 * Internal dependencies
 */
import style from './style.scss';
import { jsx as _jsx } from "react/jsx-runtime";
export default function Spinner(props) {
  const {
    progress = 0,
    testID
  } = props;
  const width = progress + '%';
  return /*#__PURE__*/_jsx(View, {
    style: [style.spinner, {
      width
    }, props.style],
    testID: testID
  });
}
//# sourceMappingURL=index.native.js.map