/**
 * External dependencies
 */
import type { ForwardedRef } from 'react';
declare const _default: import("react").ForwardRefExoticComponent<{
    children?: import("react").ReactNode;
    cycle?: boolean;
    onKeyDown?: (event: KeyboardEvent) => void;
    onNavigate?: (index: number, focusable: HTMLElement) => void;
} & {
    eventToOffset: (event: KeyboardEvent) => -1 | 0 | 1 | undefined;
    forwardedRef?: ForwardedRef<any>;
    onlyBrowserTabstops: boolean;
    stopNavigationEvents: boolean;
} & Omit<Omit<import("react").DetailedHTMLProps<import("react").HTMLAttributes<HTMLDivElement>, HTMLDivElement>, "ref">, "as" | keyof {
    children?: import("react").ReactNode;
    cycle?: boolean;
    onKeyDown?: (event: KeyboardEvent) => void;
    onNavigate?: (index: number, focusable: HTMLElement) => void;
} | "eventToOffset" | "forwardedRef" | "onlyBrowserTabstops" | "stopNavigationEvents"> & import("react").RefAttributes<HTMLDivElement>>;
export default _default;
//# sourceMappingURL=container.d.ts.map