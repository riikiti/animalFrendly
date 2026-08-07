<script setup lang="ts">
/** Точки карусели из секции «Пагинация и подгрузка» борда E — фото в карточке питомца. */
defineProps<{ total: number; current: number }>()

defineEmits<{ 'update:current': [index: number] }>()
</script>

<template>
  <div class="flex items-center gap-1.5" role="tablist" aria-label="Фотографии">
    <button
      v-for="index in total"
      :key="index"
      type="button"
      role="tab"
      :aria-selected="index - 1 === current"
      :aria-label="`Фото ${index}`"
      class="h-[7px] rounded-full transition-all"
      :class="index - 1 === current ? 'w-5.5 bg-accent' : 'w-[7px] bg-hairline'"
      @click="$emit('update:current', index - 1)"
    />
  </div>
</template>
