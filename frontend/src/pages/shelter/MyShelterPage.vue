<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import * as catalogApi from '@/entities/catalog/api'
import { useCatalogStore } from '@/entities/catalog/model'
import type { Breed } from '@/entities/catalog/types'
import * as shelterApi from '@/entities/shelter/api'
import type { AdoptionRequest, Shelter, ShelterAnimal } from '@/entities/shelter/types'
import { ApiError } from '@/shared/api/http'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'

const statusLabels: Record<string, string> = {
  pending: 'На рассмотрении модератора',
  verified: 'Верифицирован',
  rejected: 'Отклонён',
}

const animalStatusLabels: Record<string, string> = {
  available: 'Ищет дом',
  reserved: 'Зарезервирован',
  adopted: 'Пристроен',
  removed: 'Снят',
}

const router = useRouter()
const catalogStore = useCatalogStore()

const isLoading = ref(true)
const shelter = ref<Shelter | null>(null)

const createForm = reactive({ legal_name: '', inn: '', description: '' })
const isCreating = ref(false)
const createError = ref('')

const contactForm = reactive({ phone: '', email: '', address: '' })
const isSavingContact = ref(false)
const contactError = ref('')
const contactSaved = ref(false)

const isUploadingPhoto = ref(false)
const photoError = ref('')

const animals = ref<ShelterAnimal[]>([])
const pendingRequests = ref<AdoptionRequest[]>([])

const breeds = ref<Breed[]>([])
const animalForm = reactive({
  speciesId: null as number | null,
  breedId: null as number | null,
  name: '',
  sex: 'male' as 'male' | 'female',
  birthdate: '',
  description: '',
  isVaccinated: false,
})
const isAddingAnimal = ref(false)
const addAnimalError = ref('')

onMounted(async () => {
  await refreshShelter()
  isLoading.value = false
})

async function refreshShelter(): Promise<void> {
  const response = await shelterApi.getMyShelter()
  shelter.value = response.data

  if (shelter.value) {
    contactForm.phone = shelter.value.phone ?? ''
    contactForm.email = shelter.value.email ?? ''
    contactForm.address = shelter.value.address ?? ''
  }

  if (shelter.value?.verification_status === 'verified') {
    await catalogStore.ensureSpeciesLoaded()
    const first = catalogStore.species[0]
    if (first) await selectSpecies(first.slug, first.id)
    await refreshAnimals()
    await refreshPendingRequests()
  }
}

async function refreshAnimals(): Promise<void> {
  if (!shelter.value) return
  const response = await shelterApi.listMyShelterAnimals(shelter.value.id)
  animals.value = response.data
}

async function refreshPendingRequests(): Promise<void> {
  if (!shelter.value) return
  const response = await shelterApi.listPendingRequestsForShelter(shelter.value.id)
  pendingRequests.value = response.data
}

async function createShelter(): Promise<void> {
  createError.value = ''
  isCreating.value = true

  try {
    const response = await shelterApi.createShelter({
      legal_name: createForm.legal_name,
      inn: createForm.inn.trim() || null,
      description: createForm.description.trim() || null,
    })
    shelter.value = response.data
  } catch (e) {
    createError.value =
      e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  } finally {
    isCreating.value = false
  }
}

async function saveContact(): Promise<void> {
  if (!shelter.value) return

  contactError.value = ''
  contactSaved.value = false
  isSavingContact.value = true

  try {
    const response = await shelterApi.updateShelter(shelter.value.id, {
      phone: contactForm.phone.trim() || null,
      email: contactForm.email.trim() || null,
      address: contactForm.address.trim() || null,
    })
    shelter.value = response.data
    contactSaved.value = true
  } catch (e) {
    contactError.value =
      e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  } finally {
    isSavingContact.value = false
  }
}

async function onPhotoSelected(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file || !shelter.value) return

  photoError.value = ''
  isUploadingPhoto.value = true

  try {
    const response = await shelterApi.uploadShelterPhoto(shelter.value.id, file)
    shelter.value = response.data
  } catch (e) {
    photoError.value = e instanceof ApiError ? e.message : 'Не удалось загрузить фото.'
  } finally {
    isUploadingPhoto.value = false
  }
}

async function selectSpecies(slug: string, id: number): Promise<void> {
  animalForm.speciesId = id
  animalForm.breedId = null
  const response = await catalogApi.listBreeds(slug)
  breeds.value = response.data
}

async function addAnimal(): Promise<void> {
  if (!shelter.value || animalForm.speciesId === null) return

  addAnimalError.value = ''
  isAddingAnimal.value = true

  try {
    await shelterApi.addShelterAnimal(shelter.value.id, {
      species_id: animalForm.speciesId,
      breed_id: animalForm.breedId,
      name: animalForm.name,
      sex: animalForm.sex,
      birthdate: animalForm.birthdate || null,
      description: animalForm.description.trim() || null,
      is_vaccinated: animalForm.isVaccinated,
    })
    animalForm.name = ''
    animalForm.birthdate = ''
    animalForm.description = ''
    animalForm.isVaccinated = false
    await refreshAnimals()
  } catch (e) {
    addAnimalError.value =
      e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  } finally {
    isAddingAnimal.value = false
  }
}

async function decide(requestId: string, approve: boolean): Promise<void> {
  await shelterApi.decideAdoptionRequest(requestId, approve)
  await refreshPendingRequests()
  await refreshAnimals()
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center gap-3 px-2">
      <button class="text-sm text-ink-faint" @click="router.back()">←</button>
      <span class="font-display text-lg text-ink">Мой приют</span>
    </div>

    <template v-if="!isLoading">
      <form v-if="!shelter" class="flex flex-col gap-3 px-2" @submit.prevent="createShelter">
        <p class="text-xs text-ink-faint">
          Приют нужно создать один раз — после этого модератор проверит данные, и можно будет
          добавлять животных, ищущих дом.
        </p>
        <BaseInput
          v-model="createForm.legal_name"
          label="Название приюта"
          placeholder="Добрые лапы"
        />
        <BaseInput v-model="createForm.inn" label="ИНН (необязательно)" placeholder="7701234567" />
        <div class="flex flex-col gap-2">
          <span class="text-xs font-semibold text-ink-soft">О приюте</span>
          <textarea
            v-model="createForm.description"
            rows="3"
            placeholder="Расскажите о приюте"
            class="resize-none rounded-xl bg-surface-soft px-3.5 py-2.5 text-sm text-ink outline-none placeholder:text-ink-faint"
          />
        </div>
        <p v-if="createError" class="text-xs text-danger">{{ createError }}</p>
        <BaseButton type="submit" :disabled="isCreating || !createForm.legal_name">
          {{ isCreating ? 'Создаём…' : 'Создать приют' }}
        </BaseButton>
      </form>

      <template v-else>
        <div class="flex flex-col gap-2 rounded-2xl border border-hairline p-4">
          <div class="flex items-center justify-between">
            <span class="text-base font-semibold text-ink">{{ shelter.legal_name }}</span>
            <span
              class="rounded-full px-2 py-1 text-xs font-semibold"
              :class="{
                'bg-accent/20 text-accent-ink': shelter.verification_status === 'pending',
                'bg-teal-soft text-teal': shelter.verification_status === 'verified',
                'bg-clay-soft text-clay': shelter.verification_status === 'rejected',
              }"
            >
              {{ statusLabels[shelter.verification_status] }}
            </span>
          </div>
          <p v-if="shelter.description" class="text-xs text-ink-soft">{{ shelter.description }}</p>
        </div>

        <div class="flex items-center gap-3 px-2">
          <div
            class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-teal-soft text-lg font-semibold text-teal"
          >
            <img
              v-if="shelter.photo_url"
              :src="shelter.photo_url"
              class="h-full w-full object-cover"
              alt=""
            />
            <span v-else>{{ shelter.legal_name.charAt(0) }}</span>
          </div>
          <label class="text-xs font-semibold text-teal">
            {{ isUploadingPhoto ? 'Загрузка…' : 'Изменить фото приюта' }}
            <input
              type="file"
              accept="image/*"
              class="hidden"
              :disabled="isUploadingPhoto"
              @change="onPhotoSelected"
            />
          </label>
        </div>
        <p v-if="photoError" class="px-2 text-xs text-danger">{{ photoError }}</p>

        <form class="flex flex-col gap-3 px-2" @submit.prevent="saveContact">
          <BaseInput v-model="contactForm.phone" label="Телефон" placeholder="+7 926 123-45-67" />
          <BaseInput v-model="contactForm.email" label="Почта" placeholder="shelter@example.com" />
          <BaseInput v-model="contactForm.address" label="Адрес" placeholder="Город, улица, дом" />
          <p v-if="shelter.city" class="text-xs text-ink-faint">
            Распознанный город: {{ shelter.city }}
          </p>
          <p v-if="contactError" class="text-xs text-danger">{{ contactError }}</p>
          <p v-if="contactSaved" class="text-xs text-teal">Сохранено</p>
          <BaseButton type="submit" variant="outline" :disabled="isSavingContact">
            {{ isSavingContact ? 'Сохраняем…' : 'Сохранить контакты' }}
          </BaseButton>
        </form>

        <p
          v-if="shelter.verification_status !== 'verified'"
          class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
        >
          Как только модератор подтвердит приют, здесь можно будет добавлять животных и обрабатывать
          заявки на пристройство.
        </p>

        <template v-else>
          <div class="flex flex-col gap-3 border-t border-hairline px-2 pt-4">
            <span class="font-display text-base text-ink">Добавить животное</span>
            <form class="flex flex-col gap-3" @submit.prevent="addAnimal">
              <BaseInput v-model="animalForm.name" label="Кличка" placeholder="Рыжик" />

              <div class="flex flex-col gap-2">
                <span class="text-xs font-semibold text-ink-soft">Вид</span>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="s in catalogStore.species"
                    :key="s.id"
                    type="button"
                    class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
                    :class="
                      animalForm.speciesId === s.id
                        ? 'bg-teal text-white'
                        : 'bg-surface-soft text-ink-soft'
                    "
                    @click="selectSpecies(s.slug, s.id)"
                  >
                    {{ s.name }}
                  </button>
                </div>
              </div>

              <div v-if="breeds.length > 0" class="flex flex-col gap-2">
                <span class="text-xs font-semibold text-ink-soft">Порода</span>
                <select
                  v-model="animalForm.breedId"
                  class="rounded-xl bg-surface-soft px-3.5 py-2.5 text-sm text-ink outline-none"
                >
                  <option :value="null">Не указана</option>
                  <option v-for="b in breeds" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
              </div>

              <div class="flex gap-2">
                <button
                  type="button"
                  class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
                  :class="
                    animalForm.sex === 'male'
                      ? 'bg-teal text-white'
                      : 'bg-surface-soft text-ink-soft'
                  "
                  @click="animalForm.sex = 'male'"
                >
                  Мальчик
                </button>
                <button
                  type="button"
                  class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
                  :class="
                    animalForm.sex === 'female'
                      ? 'bg-teal text-white'
                      : 'bg-surface-soft text-ink-soft'
                  "
                  @click="animalForm.sex = 'female'"
                >
                  Девочка
                </button>
              </div>

              <BaseInput
                v-model="animalForm.birthdate"
                type="date"
                label="Дата рождения (необязательно)"
              />

              <textarea
                v-model="animalForm.description"
                rows="2"
                placeholder="О питомце"
                class="resize-none rounded-xl bg-surface-soft px-3.5 py-2.5 text-sm text-ink outline-none placeholder:text-ink-faint"
              />

              <label class="flex items-center gap-2 text-sm text-ink-soft">
                <input v-model="animalForm.isVaccinated" type="checkbox" />
                Привит
              </label>

              <p v-if="addAnimalError" class="text-xs text-danger">{{ addAnimalError }}</p>
              <BaseButton
                type="submit"
                :disabled="isAddingAnimal || !animalForm.name || animalForm.speciesId === null"
              >
                {{ isAddingAnimal ? 'Добавляем…' : 'Добавить животное' }}
              </BaseButton>
            </form>
          </div>

          <div class="flex flex-col gap-2 border-t border-hairline px-2 pt-4">
            <span class="font-display text-base text-ink">Заявки на пристройство</span>
            <p v-if="pendingRequests.length === 0" class="text-xs text-ink-faint">
              Пока нет заявок на рассмотрении
            </p>
            <div
              v-for="request in pendingRequests"
              :key="request.id"
              class="flex flex-col gap-2 rounded-2xl border border-hairline p-3"
            >
              <p v-if="request.message" class="text-xs text-ink-soft">{{ request.message }}</p>
              <div class="flex gap-2">
                <BaseButton @click="decide(request.id, true)">Одобрить</BaseButton>
                <BaseButton variant="ghost" @click="decide(request.id, false)"
                  >Отклонить</BaseButton
                >
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-2 border-t border-hairline px-2 pb-6 pt-4">
            <span class="font-display text-base text-ink">Животные приюта</span>
            <p v-if="animals.length === 0" class="text-xs text-ink-faint">
              Пока нет добавленных животных
            </p>
            <div
              v-for="animal in animals"
              :key="animal.id"
              class="flex items-center justify-between rounded-2xl border border-hairline p-3"
            >
              <span class="text-sm text-ink">{{ animal.pet?.name ?? 'Питомец' }}</span>
              <span class="rounded-full bg-surface-soft px-2 py-1 text-xs text-ink-soft">
                {{ animalStatusLabels[animal.status] }}
              </span>
            </div>
          </div>
        </template>
      </template>
    </template>
  </div>
</template>
