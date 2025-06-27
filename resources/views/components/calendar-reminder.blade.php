@props([
    'expiry',                   // Carbon|DateTimeInterface|string
    'title'       => 'Token expires soon',
    'label'       => 'Add reminder',
    'description' => 'Just a friendly reminder.',
])

<div
    x-data="{
        expiry : '{{ \Illuminate\Support\Carbon::parse($expiry)->toIso8601String() }}',
        title  : @js($title),
        desc   : @js($description),
        
        icsEscape (s) {
            return s
                /* 1 – escape  \  ;  ,   */
                .replace(/[\\;,]/g, m => '\\' + m)
                /* 2 – real LF  →  \n   */
                .replace(/\r?\n/g, '\\n');
        },

        download () {
            const expires  = new Date(this.expiry);
            const lifetime = (expires - Date.now()) / 86_400_000;
            const leadDays = lifetime < 14 ? 1 : 5;
            const remindOn = new Date(expires);
            remindOn.setDate(remindOn.getDate() - leadDays);

            const ymd   = d => d.toISOString().slice(0,10).replace(/-/g,'');
            const stamp = new Date().toISOString().replace(/[-:]/g,'').split('.')[0] + 'Z';
            const esc   = s => s.replace(/[\\;,]/g, m => '\\' + m);

            const ics = [
                'BEGIN:VCALENDAR',
                'VERSION:2.0',
                'PRODID:-//Ghostable//Inline Reminder//EN',
                'BEGIN:VEVENT',
                `UID:${crypto.randomUUID()}@ghostable.app`,
                `DTSTAMP:${stamp}`,
                `DTSTART;VALUE=DATE:${ymd(remindOn)}`,
                `SUMMARY:${this.icsEscape(this.title)}`,
                `DESCRIPTION:${this.icsEscape(this.desc)}`,
                'END:VEVENT',
                'END:VCALENDAR',
            ].join('\r\n');

            const blob = new Blob([ics], { type:'text/calendar' });
            const url  = URL.createObjectURL(blob);
            Object.assign(document.createElement('a'), {
                href: url,
                download: 'ghostable-reminder.ics',
            }).click();
            URL.revokeObjectURL(url);
        }
    }">
    <flux:link {{ $attributes }} x-on:click="download()">
        {{ $label }}
    </flux:link>
</div>