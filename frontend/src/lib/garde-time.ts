export type GardeStatus = 'upcoming' | 'ongoing' | 'past'

// Les heures de garde sont stockées comme heure murale, on compare donc à l'heure locale.
export function localNow(): string {
    const date = new Date()
    const pad = (value: number) => String(value).padStart(2, '0')
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

export function gardeStatus(garde: { start: string; end: string }, now: string): GardeStatus {
    if (now < garde.start.slice(0, 16)) return 'upcoming'
    if (now < garde.end.slice(0, 16)) return 'ongoing'
    return 'past'
}
