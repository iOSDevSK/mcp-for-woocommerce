/**
 * External dependencies
 */
import { useStoreState } from '@ariakit/react';

/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */

import { TabPanel as StyledTabPanel } from './styles';
import warning from '@wordpress/warning';
import { useTabsContext } from './context';
import { jsx as _jsx } from "react/jsx-runtime";
export const TabPanel = forwardRef(function TabPanel({
  children,
  tabId,
  focusable = true,
  ...otherProps
}, ref) {
  const context = useTabsContext();
  const selectedId = useStoreState(context?.store, 'selectedId');
  if (!context) {
    globalThis.SCRIPT_DEBUG === true ? warning('`Tabs.TabPanel` must be wrapped in a `Tabs` component.') : void 0;
    return null;
  }
  const {
    store,
    instanceId
  } = context;
  const instancedTabId = `${instanceId}-${tabId}`;
  return /*#__PURE__*/_jsx(StyledTabPanel, {
    ref: ref,
    store: store
    // For TabPanel, the id passed here is the id attribute of the DOM
    // element.
    // `tabId` is the id of the tab that controls this panel.
    ,
    id: `${instancedTabId}-view`,
    tabId: instancedTabId,
    focusable: focusable,
    ...otherProps,
    children: selectedId === instancedTabId && children
  });
});
//# sourceMappingURL=tabpanel.js.map