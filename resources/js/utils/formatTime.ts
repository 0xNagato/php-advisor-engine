type TimeFormat = 'h:mm A' | 'h:mm' | 'hA' | 'h A' | 'HH:mm';

export function formatTime(
    timeString: string,
    format: TimeFormat = 'h:mm A',
): string {
    const [hours, minutes] = timeString.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const hours12 = hours % 12 || 12;
    const hours24 = hours.toString().padStart(2, '0');
    const paddedMinutes = minutes.toString().padStart(2, '0');

    const formatMap: { [key in TimeFormat]: string } = {
        'h:mm A': `${hours12}:${paddedMinutes} ${period}`,
        'h:mm': `${hours12}:${paddedMinutes}`,
        hA: `${hours12}${period}`,
        'h A': `${hours12} ${period}`,
        'HH:mm': `${hours24}:${paddedMinutes}`,
    };

    return formatMap[format] || formatMap['h:mm A'];
}

export default formatTime;
