/**
 * WordPress dependencies
 */
import { chevronDown, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { chevronIconSize, DownArrowWrapper, InputControlSuffixWrapperWithClickThrough } from './styles/select-control-styles';
import { jsx as _jsx } from "react/jsx-runtime";
const SelectControlChevronDown = () => {
  return /*#__PURE__*/_jsx(InputControlSuffixWrapperWithClickThrough, {
    children: /*#__PURE__*/_jsx(DownArrowWrapper, {
      children: /*#__PURE__*/_jsx(Icon, {
        icon: chevronDown,
        size: chevronIconSize
      })
    })
  });
};
export default SelectControlChevronDown;
//# sourceMappingURL=chevron-down.js.map