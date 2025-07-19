/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';
import warning from '@wordpress/warning';
function ToolbarItem({
  children,
  ...props
}, ref) {
  if (typeof children !== 'function') {
    globalThis.SCRIPT_DEBUG === true ? warning('`ToolbarItem` is a generic headless component that accepts only function children props') : void 0;
    return null;
  }
  return children({
    ...props,
    ref
  });
}
export default forwardRef(ToolbarItem);
//# sourceMappingURL=index.native.js.map