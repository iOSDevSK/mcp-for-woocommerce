/**
 * Internal dependencies
 */

import { createHigherOrderComponent } from '../../utils/create-higher-order-component';
import useInstanceId from '../../hooks/use-instance-id';
import { jsx as _jsx } from "react/jsx-runtime";
/**
 * A Higher Order Component used to provide a unique instance ID by component.
 */
const withInstanceId = createHigherOrderComponent(WrappedComponent => {
  return props => {
    const instanceId = useInstanceId(WrappedComponent);
    // @ts-ignore
    return /*#__PURE__*/_jsx(WrappedComponent, {
      ...props,
      instanceId: instanceId
    });
  };
}, 'instanceId');
export default withInstanceId;
//# sourceMappingURL=index.js.map