<template>
  <main class="min-h-screen flex flex-col md:flex-row overflow-hidden bg-surface-bright font-body-md text-on-surface">
    <!-- Branding Area (Left Side) -->
    <section class="hidden md:flex md:w-5/12 emerald-gradient p-16 flex-col justify-between relative overflow-hidden">
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-[-10%] left-[-10%] w-[400px] h-[400px] rounded-full bg-primary-fixed blur-[100px]"></div>
        <div class="absolute bottom-[-5%] right-[-5%] w-[300px] h-[300px] rounded-full bg-tertiary-fixed blur-[80px]"></div>
      </div>
      
      <div class="relative z-10">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center backdrop-blur-md border border-white/20">
            <span class="material-symbols-outlined text-primary-fixed text-3xl">auto_stories</span>
          </div>
          <span class="font-headline-md text-white text-2xl font-bold tracking-tight">Perpustakaan Digital</span>
        </div>
      </div>
      
      <div class="relative z-10 flex flex-col gap-8">
        <img class="w-full max-w-[280px] mx-auto drop-shadow-2xl animate-pulse-slow object-contain" alt="Logo SMA Negeri 4 Jember" src="/images/logo-sman4-jember.png"/>
        <div class="max-w-md">
          <p class="font-display-lg text-white text-4xl leading-tight mb-4">
            "Membaca adalah jendela dunia, dan digitalisasi adalah kuncinya."
          </p>
          <p class="text-primary-fixed/80 text-lg">SMA Negeri 4 Jember — Berbagi Pengetahuan, Membangun Masa Depan.</p>
        </div>
      </div>
      
      <div class="relative z-10 text-white/40 text-sm">
        &copy; {{ new Date().getFullYear() }} SMA Negeri 4 Jember. Professional Education Ecosystem.
      </div>
    </section>

    <!-- Form Area (Right Side) -->
    <section class="flex-1 bg-surface-bright flex items-center justify-center p-6 md:p-12 overflow-y-auto">
      <div class="w-full max-w-lg">
        <slot />
      </div>
    </section>
  </main>
  <ToastNotification />
</template>

<script setup>
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ToastNotification from '@/Components/ToastNotification.vue'
import { useNotificationStore } from '@/stores/notification'

const page = usePage()
const notificationStore = useNotificationStore()

watch(() => page.props.flash, (flash) => {
  if (flash?.success) notificationStore.success(flash.success)
  else if (flash?.error) notificationStore.error(flash.error)
  else if (flash?.warning) notificationStore.warning(flash.warning)
  else if (flash?.info) notificationStore.info(flash.info)
}, { deep: true, immediate: true })
</script>
