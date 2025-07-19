import type { ToggleGroupControlProps, ToggleGroupControlOptionBaseProps } from '../types';
export declare const LabelView: import("@emotion/styled").StyledComponent<{
    theme?: import("@emotion/react").Theme;
    as?: React.ElementType;
}, import("react").DetailedHTMLProps<import("react").HTMLAttributes<HTMLDivElement>, HTMLDivElement>, {}>;
export declare const labelBlock: import("@emotion/utils").SerializedStyles;
export declare const buttonView: ({ isDeselectable, isIcon, isPressed, size, }: Pick<ToggleGroupControlProps, "isDeselectable" | "size"> & Pick<ToggleGroupControlOptionBaseProps, "isIcon"> & {
    isPressed?: boolean;
}) => import("@emotion/utils").SerializedStyles;
export declare const ButtonContentView: import("@emotion/styled").StyledComponent<{
    theme?: import("@emotion/react").Theme;
    as?: React.ElementType;
}, import("react").DetailedHTMLProps<import("react").HTMLAttributes<HTMLDivElement>, HTMLDivElement>, {}>;
//# sourceMappingURL=styles.d.ts.map