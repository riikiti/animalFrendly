/**
 * Деньги приходят с бэкенда в копейках целым числом (см. Shared\Domain\ValueObjects\Money).
 * Формат один на всё приложение, поэтому живёт здесь, а не копией на каждом экране.
 */
export function formatPrice(minorUnits: number, currency = 'RUB'): string {
  const amount = (minorUnits / 100).toLocaleString('ru-RU')

  return `${amount} ${currency === 'RUB' ? '₽' : currency}`
}
