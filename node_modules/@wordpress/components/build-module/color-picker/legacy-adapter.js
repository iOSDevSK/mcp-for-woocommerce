/**
 * Internal dependencies
 */
import ColorPicker from './component';
import { useDeprecatedProps } from './use-deprecated-props';
import { jsx as _jsx } from "react/jsx-runtime";
export const LegacyAdapter = props => {
  return /*#__PURE__*/_jsx(ColorPicker, {
    ...useDeprecatedProps(props)
  });
};
//# sourceMappingURL=legacy-adapter.js.map