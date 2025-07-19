/**
 * External dependencies
 */
import type { MiddlewareState } from '@floating-ui/react-dom';
export declare function overlayMiddlewares(): ({
    name: string;
    options?: any;
    fn: (state: MiddlewareState) => import("@floating-ui/core").MiddlewareReturn | Promise<import("@floating-ui/core").MiddlewareReturn>;
} | {
    name: string;
    fn({ rects }: MiddlewareState): import("@floating-ui/utils").Rect;
})[];
//# sourceMappingURL=overlay-middlewares.d.ts.map