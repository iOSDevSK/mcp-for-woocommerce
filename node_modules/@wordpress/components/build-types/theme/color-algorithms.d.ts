/**
 * Internal dependencies
 */
import type { ThemeInputValues, ThemeOutputValues } from './types';
export declare function generateThemeVariables(inputs: ThemeInputValues): ThemeOutputValues;
export declare function checkContrasts(inputs: ThemeInputValues, outputs: ThemeOutputValues['colors']): {
    accent: string | undefined;
    foreground: string | undefined;
    grays: string | undefined;
};
export declare function generateShades(background: string, foreground: string): NonNullable<ThemeOutputValues["colors"]["gray"]>;
//# sourceMappingURL=color-algorithms.d.ts.map