/**
 * WordPress dependencies
 */
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import DropdownMenu from '../../dropdown-menu';
import ToolbarContext from '../toolbar-context';
import ToolbarItem from '../toolbar-item';
import { jsx as _jsx } from "react/jsx-runtime";
function ToolbarGroupCollapsed({
  controls = [],
  toggleProps,
  ...props
}) {
  // It'll contain state if `ToolbarGroup` is being used within
  // `<Toolbar label="label" />`
  const accessibleToolbarState = useContext(ToolbarContext);
  const renderDropdownMenu = internalToggleProps => /*#__PURE__*/_jsx(DropdownMenu, {
    controls: controls,
    toggleProps: {
      ...internalToggleProps,
      'data-toolbar-item': true
    },
    ...props
  });
  if (accessibleToolbarState) {
    return /*#__PURE__*/_jsx(ToolbarItem, {
      ...toggleProps,
      children: renderDropdownMenu
    });
  }
  return renderDropdownMenu(toggleProps);
}
export default ToolbarGroupCollapsed;
//# sourceMappingURL=toolbar-group-collapsed.js.map