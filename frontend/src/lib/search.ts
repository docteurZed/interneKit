const LIGATURES: Record<string, string> = {
    œ: 'oe',
    æ: 'ae',
    ß: 'ss',
    ø: 'o',
    đ: 'd',
    ł: 'l',
    ħ: 'h',
}

export function normalizeSearch(value: unknown): string {
    if (value === null || value === undefined) return ''

    return String(value)
        .toLocaleLowerCase('fr-FR')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[œæßøđłħ]/g, (char) => LIGATURES[char] ?? char)
        .replace(/[^\p{L}\p{N}]+/gu, ' ')
        .trim()
}

export function matchesSearch(haystack: unknown, query: string): boolean {
    const needle = normalizeSearch(query)
    return !needle || normalizeSearch(haystack).includes(needle)
}
