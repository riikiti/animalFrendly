import type { PetSocialTag } from './types'

export const socialTagOptions: ReadonlyArray<{ value: PetSocialTag; title: string }> = [
  { value: 'walks', title: 'Прогулки' },
  { value: 'friendship', title: 'Знакомства' },
  { value: 'mating', title: 'Вязка' },
  { value: 'training', title: 'Дрессировка' },
  { value: 'pet_sitting', title: 'Передержка' },
]

const labelByValue = new Map(socialTagOptions.map((option) => [option.value, option.title]))

export function socialTagLabel(tag: PetSocialTag): string {
  return labelByValue.get(tag) ?? tag
}
