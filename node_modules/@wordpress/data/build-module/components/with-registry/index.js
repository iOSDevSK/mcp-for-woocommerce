/**
 * WordPress dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { RegistryConsumer } from '../registry-provider';

/**
 * Higher-order component which renders the original component with the current
 * registry context passed as its `registry` prop.
 *
 * @param {Component} OriginalComponent Original component.
 *
 * @return {Component} Enhanced component.
 */
import { jsx as _jsx } from "react/jsx-runtime";
const withRegistry = createHigherOrderComponent(OriginalComponent => props => /*#__PURE__*/_jsx(RegistryConsumer, {
  children: registry => /*#__PURE__*/_jsx(OriginalComponent, {
    ...props,
    registry: registry
  })
}), 'withRegistry');
export default withRegistry;
//# sourceMappingURL=index.js.map