import type { SelectControlProps } from '../types';
interface SelectProps extends Pick<SelectControlProps, '__next40pxDefaultSize' | 'disabled' | 'multiple' | 'variant'> {
    selectSize?: SelectControlProps['size'];
}
export declare const StyledInputBase: import("@emotion/styled").StyledComponent<import("../../input-control/types").InputBaseProps & import("react").RefAttributes<any> & Omit<Omit<import("react").DetailedHTMLProps<import("react").HTMLAttributes<HTMLDivElement>, HTMLDivElement>, "ref">, "as" | keyof import("react").RefAttributes<any> | keyof import("../../input-control/types").InputBaseProps> & {
    as?: keyof JSX.IntrinsicElements | undefined;
} & {
    theme?: import("@emotion/react").Theme;
} & SelectProps, {}, {}>;
export declare const chevronIconSize = 18;
export declare const Select: import("@emotion/styled").StyledComponent<{
    theme?: import("@emotion/react").Theme;
    as?: React.ElementType;
} & SelectProps, import("react").DetailedHTMLProps<import("react").SelectHTMLAttributes<HTMLSelectElement>, HTMLSelectElement>, {}>;
export declare const DownArrowWrapper: import("@emotion/styled").StyledComponent<{
    theme?: import("@emotion/react").Theme;
    as?: React.ElementType;
}, import("react").DetailedHTMLProps<import("react").HTMLAttributes<HTMLDivElement>, HTMLDivElement>, {}>;
export declare const InputControlSuffixWrapperWithClickThrough: import("@emotion/styled").StyledComponent<import("../../input-control/types").PrefixSuffixWrapperProps & import("react").RefAttributes<any> & Omit<Omit<import("react").DetailedHTMLProps<import("react").HTMLAttributes<HTMLDivElement>, HTMLDivElement>, "ref">, "as" | keyof import("react").RefAttributes<any> | keyof import("../../input-control/types").PrefixSuffixWrapperProps> & {
    as?: keyof JSX.IntrinsicElements | undefined;
} & {
    theme?: import("@emotion/react").Theme;
}, {}, {}>;
export {};
//# sourceMappingURL=select-control-styles.d.ts.map