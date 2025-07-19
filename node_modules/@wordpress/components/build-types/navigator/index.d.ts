export { useNavigator } from './use-navigator';
/**
 * The `Navigator` component allows rendering nested views/panels/menus
 * (via the `Navigator.Screen` component) and navigate between them
 * (via the `Navigator.Button` and `Navigator.BackButton` components).
 *
 * ```jsx
 * import { Navigator } from '@wordpress/components';
 *
 * const MyNavigation = () => (
 *   <Navigator initialPath="/">
 *     <Navigator.Screen path="/">
 *       <p>This is the home screen.</p>
 *        <Navigator.Button path="/child">
 *          Navigate to child screen.
 *       </Navigator.Button>
 *     </Navigator.Screen>
 *
 *     <Navigator.Screen path="/child">
 *       <p>This is the child screen.</p>
 *       <Navigator.BackButton>
 *         Go back
 *       </Navigator.BackButton>
 *     </Navigator.Screen>
 *   </Navigator>
 * );
 * ```
 */
export declare const Navigator: import("../context").WordPressComponent<"div", import("./types").NavigatorProps & import("react").RefAttributes<any>, true> & {
    /**
     * The `Navigator.Screen` component represents a single view/screen/panel and
     * should be used in combination with the `Navigator`, the `Navigator.Button`
     * and the `Navigator.BackButton` components.
     *
     * @example
     * ```jsx
     * import { Navigator } from '@wordpress/components';
     *
     * const MyNavigation = () => (
     *   <Navigator initialPath="/">
     *     <Navigator.Screen path="/">
     *       <p>This is the home screen.</p>
     *        <Navigator.Button path="/child">
     *          Navigate to child screen.
     *       </Navigator.Button>
     *     </Navigator.Screen>
     *
     *     <Navigator.Screen path="/child">
     *       <p>This is the child screen.</p>
     *       <Navigator.BackButton>
     *         Go back
     *       </Navigator.BackButton>
     *     </Navigator.Screen>
     *   </Navigator>
     * );
     * ```
     */
    Screen: import("../context").WordPressComponent<"div", import("./types").NavigatorScreenProps & import("react").RefAttributes<any>, false> & {
        displayName: string;
    };
    /**
     * The `Navigator.Button` component can be used to navigate to a screen and
     * should be used in combination with the `Navigator`, the `Navigator.Screen`
     * and the `Navigator.BackButton` components.
     *
     * @example
     * ```jsx
     * import { Navigator } from '@wordpress/components';
     *
     * const MyNavigation = () => (
     *   <Navigator initialPath="/">
     *     <Navigator.Screen path="/">
     *       <p>This is the home screen.</p>
     *        <Navigator.Button path="/child">
     *          Navigate to child screen.
     *       </Navigator.Button>
     *     </Navigator.Screen>
     *
     *     <Navigator.Screen path="/child">
     *       <p>This is the child screen.</p>
     *       <Navigator.BackButton>
     *         Go back
     *       </Navigator.BackButton>
     *     </Navigator.Screen>
     *   </Navigator>
     * );
     * ```
     */
    Button: import("../context").WordPressComponent<"button", {
        __next40pxDefaultSize?: boolean;
        accessibleWhenDisabled?: boolean;
        children?: import("react").ReactNode;
        description?: string;
        icon?: import("../icon").Props["icon"];
        iconPosition?: "left" | "right";
        iconSize?: import("../icon").Props["size"];
        isBusy?: boolean;
        isDestructive?: boolean;
        isPressed?: boolean;
        label?: string;
        shortcut?: string | {
            display: string;
            ariaLabel: string;
        };
        showTooltip?: boolean;
        size?: "default" | "compact" | "small";
        text?: string;
        tooltipPosition?: import("../popover/types").PopoverProps["position"];
        variant?: "primary" | "secondary" | "tertiary" | "link";
    } & {
        disabled?: boolean;
    } & {
        path: string;
        attributeName?: string;
    } & import("react").RefAttributes<any>, true> & {
        displayName: string;
    };
    /**
     * The `Navigator.BackButton` component can be used to navigate to a screen and
     * should be used in combination with the `Navigator`, the `Navigator.Screen`
     * and the `Navigator.Button` components.
     *
     * @example
     * ```jsx
     * import { Navigator } from '@wordpress/components';
     *
     * const MyNavigation = () => (
     *   <Navigator initialPath="/">
     *     <Navigator.Screen path="/">
     *       <p>This is the home screen.</p>
     *        <Navigator.Button path="/child">
     *          Navigate to child screen.
     *       </Navigator.Button>
     *     </Navigator.Screen>
     *
     *     <Navigator.Screen path="/child">
     *       <p>This is the child screen.</p>
     *       <Navigator.BackButton>
     *         Go back
     *       </Navigator.BackButton>
     *     </Navigator.Screen>
     *   </Navigator>
     * );
     * ```
     */
    BackButton: import("../context").WordPressComponent<"button", {
        __next40pxDefaultSize?: boolean;
        accessibleWhenDisabled?: boolean;
        children?: import("react").ReactNode;
        description?: string;
        icon?: import("../icon").Props["icon"];
        iconPosition?: "left" | "right";
        iconSize?: import("../icon").Props["size"];
        isBusy?: boolean;
        isDestructive?: boolean;
        isPressed?: boolean;
        label?: string;
        shortcut?: string | {
            display: string;
            ariaLabel: string;
        };
        showTooltip?: boolean;
        size?: "default" | "compact" | "small";
        text?: string;
        tooltipPosition?: import("../popover/types").PopoverProps["position"];
        variant?: "primary" | "secondary" | "tertiary" | "link";
    } & {
        disabled?: boolean;
    } & import("react").RefAttributes<any>, true> & {
        displayName: string;
    };
};
//# sourceMappingURL=index.d.ts.map