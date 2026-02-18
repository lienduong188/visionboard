<script setup>
import { computed } from 'vue';

const props = defineProps({
    heatmap: Array,
    restDays: Array,
});

const emit = defineEmits(['select-date']);

const DAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const gridData = computed(() => {
    if (!props.heatmap || props.heatmap.length === 0) return { weeks: [], months: [] };

    const weeks = [];
    let currentWeek = [];

    // Pad first week with null cells to align to Monday
    const firstDate = new Date(props.heatmap[0].date + 'T00:00:00');
    const firstDow = (firstDate.getDay() + 6) % 7; // Mon=0, Sun=6
    for (let i = 0; i < firstDow; i++) {
        currentWeek.push(null);
    }

    props.heatmap.forEach((day) => {
        const d = new Date(day.date + 'T00:00:00');
        const dow = (d.getDay() + 6) % 7;

        if (dow === 0 && currentWeek.length > 0) {
            weeks.push([...currentWeek]);
            currentWeek = [];
        }
        currentWeek.push(day);
    });

    if (currentWeek.length > 0) {
        // Pad last week to 7 days
        while (currentWeek.length < 7) currentWeek.push(null);
        weeks.push(currentWeek);
    }

    // Build month labels based on actual week positions
    const months = [];
    let lastMonth = -1;
    weeks.forEach((week, weekIdx) => {
        const firstDay = week.find(d => d !== null);
        if (firstDay) {
            const month = new Date(firstDay.date + 'T00:00:00').getMonth();
            if (month !== lastMonth) {
                months.push({ name: MONTH_NAMES[month], weekIndex: weekIdx });
                lastMonth = month;
            }
        }
    });

    // Add spanWeeks for proportional flex layout
    const monthsWithSpan = months.map((m, i) => ({
        ...m,
        spanWeeks: (i + 1 < months.length ? months[i + 1].weekIndex : weeks.length) - m.weekIndex,
    }));

    // Set of weekIndices that start a new month (for border rendering)
    const monthStartWeeks = new Set(monthsWithSpan.slice(1).map(m => m.weekIndex));

    return { weeks, months: monthsWithSpan, monthStartWeeks };
});

// ============================================================
// Thuật toán chuyển dương lịch → âm lịch (Hồ Ngọc Đức)
// ============================================================
const TZ = 7; // GMT+7

function INT(n) { return Math.floor(n); }

function jdFromDate(dd, mm, yy) {
    const a = INT((14 - mm) / 12);
    const y = yy + 4800 - a;
    const m = mm + 12 * a - 3;
    let jd = dd + INT((153 * m + 2) / 5) + 365 * y + INT(y / 4) - INT(y / 100) + INT(y / 400) - 32045;
    if (jd < 2299161) jd = dd + INT((153 * m + 2) / 5) + 365 * y + INT(y / 4) - 32083;
    return jd;
}

function getNewMoonDay(k) {
    const T = k / 1236.85, T2 = T * T, T3 = T2 * T, dr = Math.PI / 180;
    let Jd1 = 2415020.75933 + 29.53058868 * k + 0.0001178 * T2 - 0.000000155 * T3;
    Jd1 += 0.00033 * Math.sin((166.56 + 132.87 * T - 0.009173 * T2) * dr);
    const M   = 359.2242  + 29.10535608  * k - 0.0000333  * T2 - 0.00000347  * T3;
    const Mpr = 306.0253  + 385.81691806 * k + 0.0107306  * T2 + 0.00001236  * T3;
    const F   = 21.2964   + 390.67050646 * k - 0.0016528  * T2 - 0.00000239  * T3;
    let C1 = (0.1734 - 0.000393 * T) * Math.sin(M * dr) + 0.0021 * Math.sin(2 * dr * M)
           - 0.4068 * Math.sin(Mpr * dr) + 0.0161 * Math.sin(dr * 2 * Mpr)
           - 0.0004 * Math.sin(dr * 3 * Mpr) + 0.0104 * Math.sin(dr * 2 * F)
           - 0.0051 * Math.sin(dr * (M + Mpr)) - 0.0074 * Math.sin(dr * (M - Mpr))
           + 0.0004 * Math.sin(dr * (2 * F + M)) - 0.0004 * Math.sin(dr * (2 * F - M))
           - 0.0006 * Math.sin(dr * (2 * F + Mpr)) + 0.0010 * Math.sin(dr * (2 * F - Mpr))
           + 0.0005 * Math.sin(dr * (2 * Mpr + M));
    const deltat = T < -11
        ? 0.001 + 0.000839 * T + 0.0002261 * T2 - 0.00000845 * T3 - 0.000000081 * T * T3
        : -0.000278 + 0.000265 * T + 0.000262 * T2;
    return INT(Jd1 + C1 - deltat + 0.5 + TZ / 24);
}

function getSunLongitude(jdn) {
    const T = (jdn - 2451545.5 - TZ / 24) / 36525, T2 = T * T, dr = Math.PI / 180;
    const M  = 357.52910 + 35999.05030 * T - 0.0001559 * T2 - 0.00000048 * T * T2;
    const L0 = 280.46645 + 36000.76983 * T + 0.0003032 * T2;
    let DL = (1.914600 - 0.004817 * T - 0.000014 * T2) * Math.sin(dr * M)
           + (0.019993 - 0.000101 * T) * Math.sin(dr * 2 * M)
           + 0.000290 * Math.sin(dr * 3 * M);
    let L = (L0 + DL) * dr;
    L -= Math.PI * 2 * INT(L / (Math.PI * 2));
    return INT(L / Math.PI * 6);
}

function getLunarMonth11(yy) {
    const off = jdFromDate(31, 12, yy) - 2415021;
    const k = INT(off / 29.530588853);
    let nm = getNewMoonDay(k);
    if (getSunLongitude(nm) >= 9) nm = getNewMoonDay(k - 1);
    return nm;
}

function getLeapMonthOffset(a11) {
    const k = INT((a11 - 2415021.076998695) / 29.530588853 + 0.5);
    let i = 1, last = 0, arc = getSunLongitude(getNewMoonDay(k + i));
    do { last = arc; i++; arc = getSunLongitude(getNewMoonDay(k + i)); }
    while (arc !== last && i < 14);
    return i - 1;
}

function solarToLunar(dd, mm, yy) {
    const dayNumber = jdFromDate(dd, mm, yy);
    const k = INT((dayNumber - 2415021.076998695) / 29.530588853);
    let monthStart = getNewMoonDay(k + 1);
    if (monthStart > dayNumber) monthStart = getNewMoonDay(k);
    let a11 = getLunarMonth11(yy), b11 = a11, lunarYear;
    if (a11 >= monthStart) { lunarYear = yy; a11 = getLunarMonth11(yy - 1); }
    else { lunarYear = yy + 1; b11 = getLunarMonth11(yy + 1); }
    const lunarDay = dayNumber - monthStart + 1;
    const diff = INT((monthStart - a11) / 29);
    let lunarLeap = false, lunarMonth = diff + 11;
    if (b11 - a11 > 365) {
        const leapOff = getLeapMonthOffset(a11);
        if (diff >= leapOff) { lunarMonth = diff + 10; if (diff === leapOff) lunarLeap = true; }
    }
    if (lunarMonth > 12) lunarMonth -= 12;
    if (lunarMonth >= 11 && diff < 4) lunarYear--;
    return { day: lunarDay, month: lunarMonth, year: lunarYear, isLeap: lunarLeap };
}

const CAN  = ['Giáp','Ất','Bính','Đinh','Mậu','Kỷ','Canh','Tân','Nhâm','Quý'];
const CHI  = ['Tý','Sửu','Dần','Mão','Thìn','Tỵ','Ngọ','Mùi','Thân','Dậu','Tuất','Hợi'];
const DAY_PREFIX = ['Mồng 1','Mồng 2','Mồng 3','Mồng 4','Mồng 5','Mồng 6','Mồng 7','Mồng 8','Mồng 9','Mồng 10'];

function canChi(year) {
    return `${CAN[(year + 6) % 10]} ${CHI[(year + 8) % 12]}`;
}

function lunarLabel(dateStr) {
    const [y, m, d] = dateStr.split('-').map(Number);
    const l = solarToLunar(d, m, y);
    const dayStr = l.day <= 10 ? DAY_PREFIX[l.day - 1] : String(l.day);
    const monthStr = (l.isLeap ? 'tháng nhuận ' : 'tháng ') + l.month;
    return `${dayStr} ${monthStr} năm ${canChi(l.year)}`;
}
// ============================================================

const now = new Date();
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

const getCellClass = (day) => {
    if (!day) return 'bg-transparent';
    if (day.is_rest_day) return 'bg-blue-200 dark:bg-blue-800';
    if (day.date > today) return 'bg-gray-100 dark:bg-gray-800 opacity-50';
    if (day.count === 0) return 'bg-gray-200 dark:bg-gray-700';
    if (day.count === 1) return 'bg-green-300 dark:bg-green-700';
    if (day.count === 2) return 'bg-green-400 dark:bg-green-600';
    return 'bg-green-600 dark:bg-green-500';
};

const getTooltip = (day) => {
    if (!day) return '';
    const [y, m, d] = day.date.split('-').map(Number);
    const solar = `${d}/${m}/${y}`;
    const lunar = lunarLabel(day.date);
    const dateLine = `${solar}  (${lunar})`;
    if (day.is_rest_day) return `${dateLine}\n😴 Rest Day`;
    if (day.count === 0) return `${dateLine}\nNo outputs`;
    const h = Math.floor(day.duration / 60);
    const mn = day.duration % 60;
    const dur = h > 0 ? `${h}h${mn > 0 ? mn + 'm' : ''}` : `${mn}m`;
    return `${dateLine}\n${day.count} output${day.count > 1 ? 's' : ''}, ${dur}`;
};

const isToday = (day) => day && day.date === today;
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6">
        <div class="flex w-full min-w-0">
            <!-- Weekday labels column -->
            <div class="flex flex-col shrink-0 pt-6 mr-2" style="gap: 3px; width: 22px;">
                <div
                    v-for="label in DAY_LABELS"
                    :key="label"
                    class="text-[9px] text-gray-400 dark:text-gray-500 text-right leading-none select-none"
                    style="height: 12px; line-height: 12px;"
                >{{ label }}</div>
            </div>

            <!-- Grid area: month labels + week columns -->
            <div class="flex-1 min-w-0">
                <!-- Month labels: proportional flex matching week columns -->
                <div class="flex" style="height: 20px; margin-bottom: 2px;">
                    <div
                        v-for="(month, idx) in gridData.months"
                        :key="idx"
                        class="text-[10px] text-gray-500 dark:text-gray-400 overflow-hidden whitespace-nowrap select-none pl-1"
                        :class="idx > 0 ? 'border-l-2 border-gray-400 dark:border-gray-500' : ''"
                        :style="{ flex: month.spanWeeks }"
                    >{{ month.name }}</div>
                </div>

                <!-- Week columns: flex-1 fills full width -->
                <div class="flex" style="gap: 3px;">
                    <div
                        v-for="(week, wIdx) in gridData.weeks"
                        :key="wIdx"
                        class="flex flex-col flex-1"
                        :class="gridData.monthStartWeeks.has(wIdx) ? 'border-l-2 border-gray-400 dark:border-gray-500' : ''"
                        style="gap: 3px; min-width: 6px;"
                    >
                        <div
                            v-for="(day, dIdx) in week"
                            :key="dIdx"
                            class="w-full rounded-sm cursor-pointer transition-all hover:ring-1 hover:ring-indigo-400"
                            :class="[
                                getCellClass(day),
                                isToday(day) ? 'ring-2 ring-indigo-500' : '',
                            ]"
                            style="aspect-ratio: 1;"
                            :title="getTooltip(day)"
                            @click="day && emit('select-date', day.date)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex items-center gap-4 mt-4 text-xs text-gray-500 dark:text-gray-400">
            <span>Less</span>
            <div class="flex gap-1">
                <div class="w-3 h-3 rounded-sm bg-gray-200 dark:bg-gray-700"></div>
                <div class="w-3 h-3 rounded-sm bg-green-300 dark:bg-green-700"></div>
                <div class="w-3 h-3 rounded-sm bg-green-400 dark:bg-green-600"></div>
                <div class="w-3 h-3 rounded-sm bg-green-600 dark:bg-green-500"></div>
            </div>
            <span>More</span>
            <div class="w-3 h-3 rounded-sm bg-blue-200 dark:bg-blue-800 ml-2"></div>
            <span>Rest</span>
        </div>
    </div>
</template>
