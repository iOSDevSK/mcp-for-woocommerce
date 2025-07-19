/**
 * WordPress dependencies
 */
import { useRef, useInsertionEffect, useCallback } from '@wordpress/element';

/**
 * Any function.
 */

/**
 * Creates a stable callback function that has access to the latest state and
 * can be used within event handlers and effect callbacks. Throws when used in
 * the render phase.
 *
 * @param callback The callback function to wrap.
 *
 * @example
 *
 * ```tsx
 * function Component( props ) {
 *   const onClick = useEvent( props.onClick );
 *   useEffect( () => {
 *     onClick();
 *     // Won't trigger the effect again when props.onClick is updated.
 *   }, [ onClick ] );
 *   // Won't re-render Button when props.onClick is updated (if `Button` is
 *   // wrapped in `React.memo`).
 *   return <Button onClick={ onClick } />;
 * }
 * ```
 */
export default function useEvent(
/**
 * The callback function to wrap.
 */
callback) {
  const ref = useRef(() => {
    throw new Error('Callbacks created with `useEvent` cannot be called during rendering.');
  });
  useInsertionEffect(() => {
    ref.current = callback;
  });
  return useCallback((...args) => ref.current?.(...args), []);
}
//# sourceMappingURL=index.js.map