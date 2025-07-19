/**
 * This source is a local copy of the use-lilius library, since the original
 * library is not actively maintained.
 * @see https://github.com/WordPress/gutenberg/discussions/64968
 *
 * use-lilius@2.0.5
 * https://github.com/Avarios/use-lilius
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2021-Present Danny Tatom
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
export declare enum Month {
    JANUARY = 0,
    FEBRUARY = 1,
    MARCH = 2,
    APRIL = 3,
    MAY = 4,
    JUNE = 5,
    JULY = 6,
    AUGUST = 7,
    SEPTEMBER = 8,
    OCTOBER = 9,
    NOVEMBER = 10,
    DECEMBER = 11
}
export declare enum Day {
    SUNDAY = 0,
    MONDAY = 1,
    TUESDAY = 2,
    WEDNESDAY = 3,
    THURSDAY = 4,
    FRIDAY = 5,
    SATURDAY = 6
}
export interface Options {
    /**
     * What day a week starts on within the calendar matrix.
     *
     * @default Day.SUNDAY
     */
    weekStartsOn?: Day;
    /**
     * The initial viewing date.
     *
     * @default new Date()
     */
    viewing?: Date;
    /**
     * The initial date(s) selection.
     *
     * @default []
     */
    selected?: Date[];
    /**
     * The number of months in the calendar.
     *
     * @default 1
     */
    numberOfMonths?: number;
}
export interface Returns {
    /**
     * Returns a copy of the given date with the time set to 00:00:00:00.
     */
    clearTime: (date: Date) => Date;
    /**
     * Returns whether or not a date is between 2 other dates (inclusive).
     */
    inRange: (date: Date, min: Date, max: Date) => boolean;
    /**
     * The date represented in the calendar matrix. Note that
     * the month and year are the only parts used.
     */
    viewing: Date;
    /**
     * Set the date represented in the calendar matrix. Note that
     * the month and year are the only parts used.
     */
    setViewing: React.Dispatch<React.SetStateAction<Date>>;
    /**
     * Set the viewing date to today.
     */
    viewToday: () => void;
    /**
     * Set the viewing date to the given month.
     */
    viewMonth: (month: Month) => void;
    /**
     * Set the viewing date to the month before the current.
     */
    viewPreviousMonth: () => void;
    /**
     * Set the viewing date to the month after the current.
     */
    viewNextMonth: () => void;
    /**
     * Set the viewing date to the given year.
     */
    viewYear: (year: number) => void;
    /**
     * Set the viewing date to the year before the current.
     */
    viewPreviousYear: () => void;
    /**
     * Set the viewing date to the year after the current.
     */
    viewNextYear: () => void;
    /**
     * The dates currently selected.
     */
    selected: Date[];
    /**
     * Override the currently selected dates.
     */
    setSelected: React.Dispatch<React.SetStateAction<Date[]>>;
    /**
     * Reset the selected dates to [].
     */
    clearSelected: () => void;
    /**
     * Determine whether or not a date has been selected.
     */
    isSelected: (date: Date) => boolean;
    /**
     * Select one or more dates.
     */
    select: (date: Date | Date[], replaceExisting?: boolean) => void;
    /**
     * Deselect one or more dates.
     */
    deselect: (date: Date | Date[]) => void;
    /**
     * Toggle the selection of a date.
     */
    toggle: (date: Date, replaceExisting?: boolean) => void;
    /**
     * Select a range of dates (inclusive).
     */
    selectRange: (start: Date, end: Date, replaceExisting?: boolean) => void;
    /**
     * Deselect a range of dates (inclusive).
     */
    deselectRange: (start: Date, end: Date) => void;
    /**
     * A matrix of days based on the current viewing date.
     */
    calendar: Date[][][];
}
export declare const useLilius: ({ weekStartsOn, viewing: initialViewing, selected: initialSelected, numberOfMonths, }?: Options) => Returns;
//# sourceMappingURL=index.d.ts.map