export default useUpdateEffect;
/**
 * A `React.useEffect` that will not run on the first render.
 * Source:
 * https://github.com/ariakit/ariakit/blob/main/packages/ariakit-react-core/src/utils/hooks.ts
 *
 * @param {import('react').EffectCallback} effect
 * @param {import('react').DependencyList} deps
 */
declare function useUpdateEffect(effect: import("react").EffectCallback, deps: import("react").DependencyList): void;
//# sourceMappingURL=use-update-effect.d.ts.map