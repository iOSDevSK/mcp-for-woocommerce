/**
 * External dependencies
 */
import * as Ariakit from '@ariakit/react';
/**
 * Internal dependencies
 */
import _CustomSelect from './custom-select';
import Item from './item';
import { jsx as _jsx } from "react/jsx-runtime";
function CustomSelectControlV2(props) {
  const {
    defaultValue,
    onChange,
    value,
    ...restProps
  } = props;
  // Forward props + store from v2 implementation
  const store = Ariakit.useSelectStore({
    setValue: nextValue => onChange?.(nextValue),
    defaultValue,
    value
  });
  return /*#__PURE__*/_jsx(_CustomSelect, {
    ...restProps,
    store: store
  });
}
CustomSelectControlV2.Item = Item;
export default CustomSelectControlV2;
//# sourceMappingURL=index.js.map