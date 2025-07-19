/**
 * WordPress dependencies
 */
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import * as dashicons from '../mobile/dashicons';

// A predefined SVG icon is rendered instead of Dashicon because it's not supported in the native version.
import { jsx as _jsx } from "react/jsx-runtime";
function Dashicon({
  icon,
  ...extraProps
}) {
  return /*#__PURE__*/_jsx(Icon, {
    icon: dashicons[icon] || dashicons.empty,
    ...extraProps
  });
}
export default Dashicon;
//# sourceMappingURL=index.native.js.map