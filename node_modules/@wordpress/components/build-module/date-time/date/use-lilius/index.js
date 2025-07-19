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

/**
 * External dependencies
 */
import { addMonths, addYears, eachDayOfInterval, eachMonthOfInterval, eachWeekOfInterval, endOfMonth, endOfWeek, isAfter, isBefore, isEqual, set, setMonth, setYear, startOfMonth, startOfToday, startOfWeek, subMonths, subYears } from 'date-fns';

/**
 * WordPress dependencies
 */
import { useCallback, useMemo, useState } from '@wordpress/element';
export let Month = /*#__PURE__*/function (Month) {
  Month[Month["JANUARY"] = 0] = "JANUARY";
  Month[Month["FEBRUARY"] = 1] = "FEBRUARY";
  Month[Month["MARCH"] = 2] = "MARCH";
  Month[Month["APRIL"] = 3] = "APRIL";
  Month[Month["MAY"] = 4] = "MAY";
  Month[Month["JUNE"] = 5] = "JUNE";
  Month[Month["JULY"] = 6] = "JULY";
  Month[Month["AUGUST"] = 7] = "AUGUST";
  Month[Month["SEPTEMBER"] = 8] = "SEPTEMBER";
  Month[Month["OCTOBER"] = 9] = "OCTOBER";
  Month[Month["NOVEMBER"] = 10] = "NOVEMBER";
  Month[Month["DECEMBER"] = 11] = "DECEMBER";
  return Month;
}({});
export let Day = /*#__PURE__*/function (Day) {
  Day[Day["SUNDAY"] = 0] = "SUNDAY";
  Day[Day["MONDAY"] = 1] = "MONDAY";
  Day[Day["TUESDAY"] = 2] = "TUESDAY";
  Day[Day["WEDNESDAY"] = 3] = "WEDNESDAY";
  Day[Day["THURSDAY"] = 4] = "THURSDAY";
  Day[Day["FRIDAY"] = 5] = "FRIDAY";
  Day[Day["SATURDAY"] = 6] = "SATURDAY";
  return Day;
}({});
const inRange = (date, min, max) => (isEqual(date, min) || isAfter(date, min)) && (isEqual(date, max) || isBefore(date, max));
const clearTime = date => set(date, {
  hours: 0,
  minutes: 0,
  seconds: 0,
  milliseconds: 0
});
export const useLilius = ({
  weekStartsOn = Day.SUNDAY,
  viewing: initialViewing = new Date(),
  selected: initialSelected = [],
  numberOfMonths = 1
} = {}) => {
  const [viewing, setViewing] = useState(initialViewing);
  const viewToday = useCallback(() => setViewing(startOfToday()), [setViewing]);
  const viewMonth = useCallback(month => setViewing(v => setMonth(v, month)), []);
  const viewPreviousMonth = useCallback(() => setViewing(v => subMonths(v, 1)), []);
  const viewNextMonth = useCallback(() => setViewing(v => addMonths(v, 1)), []);
  const viewYear = useCallback(year => setViewing(v => setYear(v, year)), []);
  const viewPreviousYear = useCallback(() => setViewing(v => subYears(v, 1)), []);
  const viewNextYear = useCallback(() => setViewing(v => addYears(v, 1)), []);
  const [selected, setSelected] = useState(initialSelected.map(clearTime));
  const clearSelected = () => setSelected([]);
  const isSelected = useCallback(date => selected.findIndex(s => isEqual(s, date)) > -1, [selected]);
  const select = useCallback((date, replaceExisting) => {
    if (replaceExisting) {
      setSelected(Array.isArray(date) ? date : [date]);
    } else {
      setSelected(selectedItems => selectedItems.concat(Array.isArray(date) ? date : [date]));
    }
  }, []);
  const deselect = useCallback(date => setSelected(selectedItems => Array.isArray(date) ? selectedItems.filter(s => !date.map(d => d.getTime()).includes(s.getTime())) : selectedItems.filter(s => !isEqual(s, date))), []);
  const toggle = useCallback((date, replaceExisting) => isSelected(date) ? deselect(date) : select(date, replaceExisting), [deselect, isSelected, select]);
  const selectRange = useCallback((start, end, replaceExisting) => {
    if (replaceExisting) {
      setSelected(eachDayOfInterval({
        start,
        end
      }));
    } else {
      setSelected(selectedItems => selectedItems.concat(eachDayOfInterval({
        start,
        end
      })));
    }
  }, []);
  const deselectRange = useCallback((start, end) => {
    setSelected(selectedItems => selectedItems.filter(s => !eachDayOfInterval({
      start,
      end
    }).map(d => d.getTime()).includes(s.getTime())));
  }, []);
  const calendar = useMemo(() => eachMonthOfInterval({
    start: startOfMonth(viewing),
    end: endOfMonth(addMonths(viewing, numberOfMonths - 1))
  }).map(month => eachWeekOfInterval({
    start: startOfMonth(month),
    end: endOfMonth(month)
  }, {
    weekStartsOn
  }).map(week => eachDayOfInterval({
    start: startOfWeek(week, {
      weekStartsOn
    }),
    end: endOfWeek(week, {
      weekStartsOn
    })
  }))), [viewing, weekStartsOn, numberOfMonths]);
  return {
    clearTime,
    inRange,
    viewing,
    setViewing,
    viewToday,
    viewMonth,
    viewPreviousMonth,
    viewNextMonth,
    viewYear,
    viewPreviousYear,
    viewNextYear,
    selected,
    setSelected,
    clearSelected,
    isSelected,
    select,
    deselect,
    toggle,
    selectRange,
    deselectRange,
    calendar
  };
};
//# sourceMappingURL=index.js.map