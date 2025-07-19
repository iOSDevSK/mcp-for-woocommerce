/**
 * External dependencies
 */
import { colord, extend } from 'colord';
import namesPlugin from 'colord/plugins/names';
import a11yPlugin from 'colord/plugins/a11y';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

extend([namesPlugin, a11yPlugin]);

/**
 * Checks if a color value is a simple CSS color.
 *
 * @param value The color value to check.
 * @return A boolean indicating whether the color value is a simple CSS color.
 */
const isSimpleCSSColor = value => {
  const valueIsCssVariable = /var\(/.test(value !== null && value !== void 0 ? value : '');
  const valueIsColorMix = /color-mix\(/.test(value !== null && value !== void 0 ? value : '');
  return !valueIsCssVariable && !valueIsColorMix;
};
export const extractColorNameFromCurrentValue = (currentValue, colors = [], showMultiplePalettes = false) => {
  if (!currentValue) {
    return '';
  }
  const currentValueIsSimpleColor = currentValue ? isSimpleCSSColor(currentValue) : false;
  const normalizedCurrentValue = currentValueIsSimpleColor ? colord(currentValue).toHex() : currentValue;

  // Normalize format of `colors` to simplify the following loop

  const colorPalettes = showMultiplePalettes ? colors : [{
    colors: colors
  }];
  for (const {
    colors: paletteColors
  } of colorPalettes) {
    for (const {
      name: colorName,
      color: colorValue
    } of paletteColors) {
      const normalizedColorValue = currentValueIsSimpleColor ? colord(colorValue).toHex() : colorValue;
      if (normalizedCurrentValue === normalizedColorValue) {
        return colorName;
      }
    }
  }

  // translators: shown when the user has picked a custom color (i.e not in the palette of colors).
  return __('Custom');
};

// The PaletteObject type has a `colors` property (an array of ColorObject),
// while the ColorObject type has a `color` property (the CSS color value).
export const isMultiplePaletteObject = obj => Array.isArray(obj.colors) && !('color' in obj);
export const isMultiplePaletteArray = arr => {
  return arr.length > 0 && arr.every(colorObj => isMultiplePaletteObject(colorObj));
};

/**
 * Transform a CSS variable used as background color into the color value itself.
 *
 * @param value   The color value that may be a CSS variable.
 * @param element The element for which to get the computed style.
 * @return The background color value computed from a element.
 */
export const normalizeColorValue = (value, element) => {
  if (!value || !element || isSimpleCSSColor(value)) {
    return value;
  }
  const {
    ownerDocument
  } = element;
  const {
    defaultView
  } = ownerDocument;
  const computedBackgroundColor = defaultView?.getComputedStyle(element).backgroundColor;
  return computedBackgroundColor ? colord(computedBackgroundColor).toHex() : value;
};
//# sourceMappingURL=utils.js.map