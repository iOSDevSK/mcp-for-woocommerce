/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */

import { colorVariables, Wrapper } from './styles';
import { generateThemeVariables } from './color-algorithms';
import { useCx } from '../utils';

/**
 * `Theme` allows defining theme variables for components in the `@wordpress/components` package.
 *
 * Multiple `Theme` components can be nested in order to override specific theme variables.
 *
 *
 * ```jsx
 * const Example = () => {
 *   return (
 *     <Theme accent="red">
 *       <Button variant="primary">I'm red</Button>
 *       <Theme accent="blue">
 *         <Button variant="primary">I'm blue</Button>
 *       </Theme>
 *     </Theme>
 *   );
 * };
 * ```
 */
import { jsx as _jsx } from "react/jsx-runtime";
function Theme({
  accent,
  background,
  className,
  ...props
}) {
  const cx = useCx();
  const classes = useMemo(() => cx(...colorVariables(generateThemeVariables({
    accent,
    background
  })), className), [accent, background, className, cx]);
  return /*#__PURE__*/_jsx(Wrapper, {
    className: classes,
    ...props
  });
}
export default Theme;
//# sourceMappingURL=index.js.map