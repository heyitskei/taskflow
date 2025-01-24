export function toLocalDate(utcDateString) {
    if (!utcDateString) return null;

    // Parse the UTC date string
    const [datePart, timePart] = utcDateString.split(' ');
    const [year, month, day] = datePart.split('-');
    const [hours, minutes, seconds] = timePart.split(':');

    // Create date in UTC
    return new Date(Date.UTC(
        parseInt(year),
        parseInt(month) - 1,
        parseInt(day),
        parseInt(hours),
        parseInt(minutes),
        parseInt(seconds)
    ));
}

export function toUTCString(localDate) {
    if (!localDate) return null;

    // Convert local date to UTC string in MySQL format
    const year = localDate.getUTCFullYear();
    const month = String(localDate.getUTCMonth() + 1).padStart(2, '0');
    const day = String(localDate.getUTCDate()).padStart(2, '0');
    const hours = String(localDate.getUTCHours()).padStart(2, '0');
    const minutes = String(localDate.getUTCMinutes()).padStart(2, '0');
    const seconds = String(localDate.getUTCSeconds()).padStart(2, '0');

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

export function formatTime(date) {
    if (!date) return '';
    try {
        const dateObj = new Date(date);
        if (isNaN(dateObj.getTime())) {
            console.error('Invalid date:', date);
            return 'Invalid Date';
        }
        return dateObj.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    } catch (error) {
        console.error('Error formatting date:', error);
        return 'Invalid Date';
    }
}

export function formatFullDate(date) {
    if (!date) return '';
    return new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(date);
}
