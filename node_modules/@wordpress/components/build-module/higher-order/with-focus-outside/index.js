/**
 * WordPress dependencies
 */
import { useCallback, useState } from '@wordpress/element';
import { createHigherOrderComponent, __experimentalUseFocusOutside as useFocusOutside } from '@wordpress/compose';
import { jsx as _jsx } from "react/jsx-runtime";
export default createHigherOrderComponent(WrappedComponent => props => {
  const [handleFocusOutside, setHandleFocusOutside] = useState(undefined);
  const bindFocusOutsideHandler = useCallback(node => setHandleFocusOutside(() => node?.handleFocusOutside ? node.handleFocusOutside.bind(node) : undefined), []);
  return /*#__PURE__*/_jsx("div", {
    ...useFocusOutside(handleFocusOutside),
    children: /*#__PURE__*/_jsx(WrappedComponent, {
      ref: bindFocusOutsideHandler,
      ...props
    })
  });
}, 'withFocusOutside');
//# sourceMappingURL=index.js.map