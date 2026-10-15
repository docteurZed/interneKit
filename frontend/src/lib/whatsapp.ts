export function whatsappUrl(phone: string): string | null {
    const internationalNumber = phone.replace(/\D/g, '')

    return internationalNumber ? `https://wa.me/${internationalNumber}` : null
}
