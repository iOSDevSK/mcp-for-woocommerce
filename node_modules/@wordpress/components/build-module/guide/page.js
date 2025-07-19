/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { jsx as _jsx } from "react/jsx-runtime";
export default function GuidePage(props) {
  useEffect(() => {
    deprecated('<GuidePage>', {
      since: '5.5',
      alternative: 'the `pages` prop in <Guide>'
    });
  }, []);
  return /*#__PURE__*/_jsx("div", {
    ...props
  });
}
//# sourceMappingURL=page.js.map