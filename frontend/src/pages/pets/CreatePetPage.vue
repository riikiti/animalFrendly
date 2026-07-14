<script setup lang="ts">
import CreatePetForm from '@/features/pet/create-pet/CreatePetForm.vue'
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-sm flex-col gap-6 px-6 py-10">
    <div class="flex flex-col gap-1">
      <span class="font-display text-xl text-ink">Анкета питомца</span>
      <p class="text-sm text-ink-soft">Расскажите немного о вашем питомце</p>
    </div>
    <CreatePetForm />
  </div>
</template>
