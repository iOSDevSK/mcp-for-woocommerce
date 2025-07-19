/**
 * External dependencies
 */
import type { StoryContext } from '@storybook/react';
/**
 * Internal dependencies
 */
import type { LegacyStateOptions } from '..';
/**
 * Renders a composite widget.
 *
 * This unstable component is deprecated. Use `Composite` instead.
 *
 * ```jsx
 * import {
 * 	__unstableUseCompositeState as useCompositeState,
 * 	__unstableComposite as Composite,
 * 	__unstableCompositeItem as CompositeItem,
 * } from '@wordpress/components';
 *
 * const state = useCompositeState();
 * <Composite state={ state }>
 * 	<CompositeItem>Item 1</CompositeItem>
 * 	<CompositeItem>Item 2</CompositeItem>
 * </Composite>;
 * ```
 */
export declare function UseCompositeStatePlaceholder(props: LegacyStateOptions): import("react").JSX.Element;
export declare namespace UseCompositeStatePlaceholder {
    var displayName: string;
}
export declare function transform(code: string, context: StoryContext): string;
//# sourceMappingURL=utils.d.ts.map