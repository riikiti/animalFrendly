<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import * as conversationApi from '@/entities/conversation/api'
import type { Message } from '@/entities/conversation/types'
import { useCatalogStore } from '@/entities/catalog/model'
import * as shelterApi from '@/entities/shelter/api'
import type { ShelterAnimal } from '@/entities/shelter/types'
import { useUserStore } from '@/entities/user/model'
import { ChevronLeft, MapPin, Send } from 'lucide-vue-next'
import { echo } from '@/shared/lib/echo'
import { yandexRouteUrl } from '@/shared/lib/directions'
import ConversationsList from '@/widgets/ConversationsList.vue'
import BaseAvatar from '@/shared/ui/components/BaseAvatar.vue'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()
const catalogStore = useCatalogStore()

const conversationId = ref<string | null>(null)
const messages = ref<Message[]>([])
const draft = ref('')
const counterpartAddress = ref<string | null>(null)
const counterpartLocation = ref<{ lat: number; lng: number } | null>(null)
const attachedAnimal = ref<ShelterAnimal | null>(null)

const attachedAnimalSpecies = computed(() =>
  attachedAnimal.value?.pet ? catalogStore.speciesName(attachedAnimal.value.pet.species_id) : '',
)

onMounted(async () => {
  const kind = String(route.params.kind)
  const id = String(route.params.id)

  if (kind === 'shelter' || kind === 'direct') {
    // Беседа уже создана до перехода сюда (см. «Написать в приют»/«Написать» продавцу) —
    // сам id из роута это id беседы, отдельный лукап не нужен. Метаданные (кто собеседник,
    // прикреплено ли животное) берём из общего списка бесед клиентской фильтрацией — тот же
    // приём, что уже на ShelterAnimalDetailPage.vue. Для прямого контакта (kind === 'direct')
    // shelter_animal_id всегда null, поэтому баннер животного просто не появится.
    conversationId.value = id
    const list = await conversationApi.listConversations()
    const conversation = list.data.find((c) => c.id === id)

    if (conversation?.shelter_animal_id) {
      await catalogStore.ensureSpeciesLoaded()
      const animals = await shelterApi.listAvailableShelterAnimals()
      attachedAnimal.value =
        animals.data.find((a) => a.id === conversation.shelter_animal_id) ?? null
    }
  } else {
    const conversation =
      kind === 'adoption'
        ? await conversationApi.getConversationForAdoptionRequest(id)
        : await conversationApi.getConversationForMatch(id)
    conversationId.value = conversation.data.id
    counterpartAddress.value = conversation.data.counterpart_address
    counterpartLocation.value = conversation.data.counterpart_location
  }

  await refreshMessages()

  echo
    .private(`conversation.${conversationId.value}`)
    .listen('.message.sent', (message: Message) => {
      appendMessage(message)
    })
})

// Отправитель добавляет своё сообщение локально сразу по ответу POST — не ждёт эха
// собственного WebSocket-события (под нагрузкой это может занять больше пары секунд, т.к.
// broadcast идёт через ту же очередь, что и остальные джобы). Дедуп по id на случай, если
// WS-событие всё же придёт следом.
function appendMessage(message: Message): void {
  if (messages.value.some((m) => m.id === message.id)) return
  messages.value.push(message)
}

onUnmounted(() => {
  if (conversationId.value) {
    echo.leave(`conversation.${conversationId.value}`)
  }
})

async function refreshMessages(): Promise<void> {
  if (!conversationId.value) return
  const response = await conversationApi.listMessages(conversationId.value)
  messages.value = response.data
}

async function send(): Promise<void> {
  if (!conversationId.value || draft.value.trim() === '') return

  const body = draft.value
  draft.value = ''
  const response = await conversationApi.sendMessage(conversationId.value, body)
  appendMessage(response.data)
}
</script>

<template>
  <div
    class="mx-auto h-screen max-w-sm px-4 py-4 md:max-w-lg lg:grid lg:max-w-none lg:grid-cols-[360px_1fr] lg:gap-5 lg:px-8"
  >
    <!-- Список виден только на широком экране: на телефоне переписка занимает весь экран,
    а назад к списку ведёт кнопка в шапке. -->
    <div class="hidden overflow-y-auto lg:block">
      <ConversationsList />
    </div>

    <div class="flex h-full flex-col">
      <div class="flex items-center gap-2 pb-3">
        <button
          class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
          aria-label="Назад"
          @click="router.back()"
        >
          <ChevronLeft class="size-5" />
        </button>
        <span class="font-display text-base font-bold text-ink">Чат</span>
      </div>

      <div
        v-if="attachedAnimal"
        class="flex items-center gap-3 rounded-card border border-hairline bg-surface p-2.5"
      >
        <BaseAvatar :name="attachedAnimal.pet?.name ?? undefined" size="sm" shape="rounded" />
        <div class="min-w-0">
          <p class="truncate text-sm font-bold text-ink">
            {{ attachedAnimal.pet?.name ?? 'Питомец' }}
          </p>
          <p class="text-xs text-ink-faint">{{ attachedAnimalSpecies }}</p>
        </div>
      </div>

      <div
        v-if="counterpartAddress"
        class="mt-2 flex items-center justify-between gap-2 rounded-2xl bg-surface-soft px-3 py-2.5"
      >
        <span class="inline-flex min-w-0 items-center gap-1.5 text-xs text-ink-soft">
          <MapPin class="size-3.5 shrink-0" aria-hidden="true" />
          <span class="truncate">{{ counterpartAddress }}</span>
        </span>
        <a
          v-if="counterpartLocation"
          :href="yandexRouteUrl(counterpartLocation.lat, counterpartLocation.lng)"
          target="_blank"
          rel="noopener"
          class="shrink-0 text-xs font-bold text-accent-text"
        >
          Как добраться
        </a>
      </div>

      <div class="flex-1 space-y-2 overflow-y-auto py-3">
        <div
          v-for="message in messages"
          :key="message.id"
          class="max-w-[76%] px-3.5 py-2.5 text-sm lg:max-w-lg"
          :class="
            message.sender_id === userStore.currentUser?.id
              ? 'ml-auto rounded-[18px] rounded-br-sm bg-accent text-accent-ink'
              : 'rounded-[18px] rounded-bl-sm bg-surface-soft text-ink'
          "
        >
          {{ message.body }}
        </div>
      </div>

      <form
        v-if="conversationId"
        class="flex items-center gap-2 rounded-full bg-surface-soft py-1.5 pr-1.5 pl-4"
        @submit.prevent="send"
      >
        <input
          v-model="draft"
          type="text"
          placeholder="Сообщение…"
          class="min-w-0 flex-1 bg-transparent text-[15px] text-ink outline-none placeholder:text-ink-faint"
        />
        <button
          type="submit"
          class="grid size-9 shrink-0 place-items-center rounded-full bg-accent text-accent-ink transition-colors hover:bg-accent-hover"
          aria-label="Отправить"
        >
          <Send class="size-4" />
        </button>
      </form>
    </div>
  </div>
</template>
