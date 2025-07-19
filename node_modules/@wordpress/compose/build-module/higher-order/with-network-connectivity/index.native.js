/**
 * Internal dependencies
 */
import { createHigherOrderComponent } from '../../utils/create-higher-order-component';
import useNetworkConnectivity from '../../hooks/use-network-connectivity';
import { jsx as _jsx } from "react/jsx-runtime";
const withNetworkConnectivity = createHigherOrderComponent(WrappedComponent => {
  return props => {
    const {
      isConnected
    } = useNetworkConnectivity();
    return /*#__PURE__*/_jsx(WrappedComponent, {
      ...props,
      isConnected: isConnected
    });
  };
}, 'withNetworkConnectivity');
export default withNetworkConnectivity;
//# sourceMappingURL=index.native.js.map