import { Resizable } from 're-resizable';
import type { ResizableProps } from 're-resizable';
import type { ReactNode } from 'react';
/**
 * Internal dependencies
 */
import ResizeTooltip from './resize-tooltip';
export declare const ResizableBox: import("react").ForwardRefExoticComponent<ResizableProps & {
    children: ReactNode;
    showHandle?: boolean;
    __experimentalShowTooltip?: boolean;
    __experimentalTooltipProps?: Parameters<typeof ResizeTooltip>[0];
} & import("react").RefAttributes<Resizable>>;
export default ResizableBox;
//# sourceMappingURL=index.d.ts.map