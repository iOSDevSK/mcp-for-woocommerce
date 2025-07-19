/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */
import { useCallback, useState } from '@wordpress/element';
import { createHigherOrderComponent, __experimentalUseFocusOutside as useFocusOutside } from '@wordpress/compose';
import { jsx as _jsx } from "react/jsx-runtime";
export default createHigherOrderComponent(WrappedComponent => props => {
  const [handleFocusOutside, setHandleFocusOutside] = useState();
  const bindFocusOutsideHandler = useCallback(node => setHandleFocusOutside(() => node?.handleFocusOutside ? node.handleFocusOutside.bind(node) : undefined), []);
  return /*#__PURE__*/_jsx(View, {
    ...useFocusOutside(handleFocusOutside),
    children: /*#__PURE__*/_jsx(WrappedComponent, {
      ref: bindFocusOutsideHandler,
      ...props
    })
  });
}, 'withFocusOutside');
//# sourceMappingURL=index.native.js.map