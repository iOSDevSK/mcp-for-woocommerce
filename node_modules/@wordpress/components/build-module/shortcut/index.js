import { jsx as _jsx } from "react/jsx-runtime";
/**
 * Internal dependencies
 */

/**
 * Shortcut component is used to display keyboard shortcuts, and it can be customized with a custom display and aria label if needed.
 *
 * ```jsx
 * import { Shortcut } from '@wordpress/components';
 *
 * const MyShortcut = () => {
 * 	return (
 * 		<Shortcut shortcut={{ display: 'Ctrl + S', ariaLabel: 'Save' }} />
 * 	);
 * };
 * ```
 */
function Shortcut(props) {
  const {
    shortcut,
    className
  } = props;
  if (!shortcut) {
    return null;
  }
  let displayText;
  let ariaLabel;
  if (typeof shortcut === 'string') {
    displayText = shortcut;
  }
  if (shortcut !== null && typeof shortcut === 'object') {
    displayText = shortcut.display;
    ariaLabel = shortcut.ariaLabel;
  }
  return /*#__PURE__*/_jsx("span", {
    className: className,
    "aria-label": ariaLabel,
    children: displayText
  });
}
export default Shortcut;
//# sourceMappingURL=index.js.map