<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Archive, ChevronLeft, ImagePlus, PackagePlus, Pencil, Store } from 'lucide-vue-next'
import * as shopApi from '@/entities/shop/api'
import type { ShopCategory, ShopProduct } from '@/entities/shop/types'
import { ApiError } from '@/shared/api/http'
import { formatPrice } from '@/shared/lib/money'
import { pushToast } from '@/shared/lib/toast'
import BaseAlert from '@/shared/ui/components/BaseAlert.vue'
import BaseBadge from '@/shared/ui/components/BaseBadge.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseEmptyState from '@/shared/ui/components/BaseEmptyState.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'
import BaseSelectMenu from '@/shared/ui/components/BaseSelectMenu.vue'
import BaseTextarea from '@/shared/ui/components/BaseTextarea.vue'

const router = useRouter()

const categories = ref<ShopCategory[]>([])
const products = ref<ShopProduct[]>([])
const isLoading = ref(true)
const showForm = ref(false)
/** id редактируемого товара; null — форма создаёт новый. */
const editingId = ref<string | null>(null)
const isSubmitting = ref(false)
const uploadingId = ref<string | null>(null)
const error = ref('')

const form = reactive({
  categoryId: null as string | null,
  title: '',
  description: '',
  // Цена вводится в рублях, на бэкенд уходит в копейках.
  price: '',
  stock: '1',
})

const canSubmit = computed(() => form.categoryId !== null && form.title !== '' && form.price !== '')

async function refresh(): Promise<void> {
  products.value = (await shopApi.listMyProducts()).data
}

onMounted(async () => {
  const [categoryResponse] = await Promise.all([shopApi.listCategories(), refresh()])
  categories.value = categoryResponse.data
  isLoading.value = false
})

function startCreate(): void {
  editingId.value = null
  form.categoryId = null
  form.title = ''
  form.description = ''
  form.price = ''
  form.stock = '1'
  showForm.value = true
}

function startEdit(product: ShopProduct): void {
  editingId.value = product.id
  form.categoryId = product.category_id
  form.title = product.title
  form.description = product.description ?? ''
  form.price = String(product.price_amount / 100)
  form.stock = String(product.stock)
  showForm.value = true
}

async function submit(): Promise<void> {
  if (form.categoryId === null) return
  error.value = ''
  isSubmitting.value = true

  const payload = {
    category_id: form.categoryId,
    title: form.title,
    description: form.description || null,
    price_amount: Math.round(Number(form.price) * 100),
    stock: Number(form.stock),
  }

  try {
    if (editingId.value === null) await shopApi.createProduct(payload)
    else await shopApi.updateProduct(editingId.value, payload)

    await refresh()
    showForm.value = false
    editingId.value = null
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Не получилось сохранить товар.'
  } finally {
    isSubmitting.value = false
  }
}

async function uploadPhoto(product: ShopProduct, event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  // Сбрасываем, иначе повторный выбор того же файла не вызовет change.
  input.value = ''
  if (!file) return

  error.value = ''
  uploadingId.value = product.id

  try {
    await shopApi.uploadProductPhoto(product.id, file)
    await refresh()
    pushToast({ tone: 'success', title: 'Фото загружено', description: product.title })
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Не получилось загрузить фото.'
  } finally {
    uploadingId.value = null
  }
}

async function archive(product: ShopProduct): Promise<void> {
  await shopApi.archiveProduct(product.id)
  await refresh()
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 pb-8 md:max-w-lg lg:max-w-4xl lg:px-8"
  >
    <div class="flex items-center gap-2 px-2">
      <button
        class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
        aria-label="Назад"
        @click="router.push({ name: 'shop' })"
      >
        <ChevronLeft class="size-5" />
      </button>
      <h1 class="font-display text-xl font-bold text-ink">Мои товары</h1>
    </div>

    <div class="px-2">
      <BaseButton v-if="!showForm" size="lg" block @click="startCreate">
        <PackagePlus class="size-5" aria-hidden="true" />
        Добавить товар
      </BaseButton>
    </div>

    <form v-if="showForm" class="flex flex-col gap-3.5 px-2" @submit.prevent="submit">
      <BaseSelectMenu
        v-model="form.categoryId"
        label="Категория"
        placeholder="Выберите категорию"
        :options="categories.map((category) => ({ value: category.id, label: category.name }))"
      />
      <BaseInput v-model="form.title" label="Название" placeholder="Корм для щенков" />
      <BaseTextarea
        v-model="form.description"
        label="Описание"
        :rows="3"
        placeholder="Состав, вес, для кого подходит"
      />
      <div class="flex gap-2">
        <BaseInput v-model="form.price" label="Цена, ₽" placeholder="1290" inputmode="numeric" />
        <BaseInput v-model="form.stock" label="В наличии, шт." inputmode="numeric" />
      </div>

      <BaseAlert v-if="error" tone="error">{{ error }}</BaseAlert>

      <div class="flex gap-2">
        <BaseButton variant="outline" size="lg" block @click="showForm = false">Отмена</BaseButton>
        <BaseButton type="submit" size="lg" block :loading="isSubmitting" :disabled="!canSubmit">
          {{ editingId === null ? 'Опубликовать' : 'Сохранить' }}
        </BaseButton>
      </div>
    </form>

    <BaseAlert v-else-if="error" tone="error" class="mx-2">{{ error }}</BaseAlert>

    <div v-if="!isLoading && products.length === 0 && !showForm" class="px-2">
      <BaseEmptyState
        tone="gold"
        title="У вас нет товаров"
        description="Разместите первый — его увидят владельцы питомцев рядом."
      >
        <template #icon><Store class="size-8" /></template>
      </BaseEmptyState>
    </div>

    <div v-else class="flex flex-col gap-2.5 px-2">
      <div
        v-for="product in products"
        :key="product.id"
        class="flex flex-wrap items-center gap-3 rounded-card border border-hairline bg-surface p-3"
      >
        <span
          class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-2xl bg-surface-soft"
        >
          <img
            v-if="product.photo_url"
            :src="product.photo_url"
            :alt="product.title"
            class="size-full object-cover"
          />
          <Store v-else class="size-5 text-ink-faint" aria-hidden="true" />
        </span>

        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold text-ink">{{ product.title }}</p>
          <p class="text-xs text-ink-faint">
            {{ formatPrice(product.price_amount, product.currency) }} · остаток
            {{ product.stock }}
          </p>
        </div>

        <BaseBadge :tone="product.status === 'published' ? 'teal' : 'neutral'">
          {{ product.status === 'published' ? 'В продаже' : 'В архиве' }}
        </BaseBadge>

        <label
          class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-full px-3 text-[13px] font-semibold text-ink transition-colors hover:bg-surface-soft"
        >
          <ImagePlus class="size-4" aria-hidden="true" />
          {{ uploadingId === product.id ? 'Загрузка…' : 'Фото' }}
          <input
            type="file"
            accept="image/*"
            class="sr-only"
            :disabled="uploadingId === product.id"
            @change="uploadPhoto(product, $event)"
          />
        </label>

        <BaseButton variant="ghost" size="sm" @click="startEdit(product)">
          <Pencil class="size-4" aria-hidden="true" />
          Изменить
        </BaseButton>

        <BaseButton
          v-if="product.status === 'published'"
          variant="ghost"
          size="sm"
          @click="archive(product)"
        >
          <Archive class="size-4" aria-hidden="true" />
          В архив
        </BaseButton>
      </div>
    </div>
  </div>
</template>
