/**
 * Alpine data helper for Organization → Contact support hours rows.
 */
(function (global) {
    'use strict';

    global.orgSupportHours = function orgSupportHours(config) {
        const dayOrder = Object.keys(config.days || {});
        let keySeed = 1;

        const withKeys = (rows) => (Array.isArray(rows) ? rows : []).map((row) => ({
            _key: 'sh-' + (keySeed++),
            day: row.day || 'monday',
            from: row.from || '10:00',
            to: row.to || '16:00',
            timezone: row.timezone || 'Asia/Kolkata',
        }));

        return {
            rows: withKeys(config.rows),
            days: config.days || {},
            timezones: config.timezones || {},
            min: config.min || 1,
            max: config.max || 7,
            init() {
                if (!this.rows.length) {
                    this.rows = withKeys(
                        ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'].map((day) => ({
                            day,
                            from: '10:00',
                            to: '16:00',
                            timezone: 'Asia/Kolkata',
                        }))
                    );
                }
                this.$nextTick(() => this.mountPickers());
            },
            unusedDay() {
                const used = new Set(this.rows.map((row) => row.day));
                return dayOrder.find((day) => !used.has(day)) || dayOrder[0] || 'monday';
            },
            addRow() {
                if (this.rows.length >= this.max) return;
                this.rows.push({
                    _key: 'sh-' + (keySeed++),
                    day: this.unusedDay(),
                    from: '10:00',
                    to: '16:00',
                    timezone: this.rows[0]?.timezone || 'Asia/Kolkata',
                });
                this.$nextTick(() => this.mountPickers());
            },
            removeRow(index) {
                if (this.rows.length <= this.min) return;
                const rowEl = this.$el.querySelector(`[data-hours-index="${index}"]`);
                rowEl?.querySelectorAll('[data-ems-datetime-input]').forEach((input) => {
                    if (input._flatpickr) input._flatpickr.destroy();
                });
                this.rows.splice(index, 1);
                this.$nextTick(() => this.mountPickers());
            },
            mountPickers() {
                const root = this.$el.querySelector('#org-support-hours') || this.$el;
                global.EmsDateTimePicker?.initAll?.(root)?.then?.(() => {
                    root.querySelectorAll('[data-ems-datetime-input]').forEach((input) => {
                        if (input._orgHoursBound) return;
                        input._orgHoursBound = true;
                        const sync = () => {
                            const match = String(input.name || '').match(/support_hours\[(\d+)\]\[(from|to)\]/);
                            if (!match) return;
                            const idx = Number(match[1]);
                            const field = match[2];
                            if (this.rows[idx]) {
                                this.rows[idx][field] = input.value;
                            }
                        };
                        input.addEventListener('change', sync);
                        input.addEventListener('blur', sync);
                    });
                });
            },
            formatAmPm(value) {
                const raw = String(value || '').trim();
                const withMeridiem = raw.match(/^(\d{1,2}):(\d{2})\s*([AaPp][Mm])$/);
                if (withMeridiem) {
                    let hour = Number(withMeridiem[1]);
                    const minute = withMeridiem[2];
                    const meridiem = withMeridiem[3].toUpperCase();
                    return `${hour}:${minute} ${meridiem}`;
                }
                const match = raw.match(/^(\d{1,2}):(\d{2})$/);
                if (!match) return value || '';
                let hour = Number(match[1]);
                const minute = match[2];
                const meridiem = hour >= 12 ? 'PM' : 'AM';
                hour = hour % 12;
                if (hour === 0) hour = 12;
                return `${hour}:${minute} ${meridiem}`;
            },
            summary() {
                return this.rows.map((row) => {
                    const day = this.days[row.day] || row.day;
                    const tz = row.timezone === 'Asia/Kolkata' ? 'IST' : row.timezone;
                    return `${day} ${this.formatAmPm(row.from)} – ${this.formatAmPm(row.to)} (${tz})`;
                }).join('; ');
            },
        };
    };
})(window);
