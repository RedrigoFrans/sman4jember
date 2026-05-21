<template>
  <AuthLayout>
    <div class="glass-card rounded-2xl p-8 md:p-10">
      <div class="mb-8">
        <h1 class="font-headline-md text-primary text-3xl mb-2">Selamat Datang</h1>
        <p class="text-on-surface-variant">Silakan masuk ke akun perpustakaan Anda.</p>
      </div>

      <!-- Flash success message -->
      <div v-if="$page.props.flash?.success"
        class="mb-6 flex items-center gap-3 p-4 bg-emerald-50 text-emerald-700 rounded-lg border border-emerald-200">
        <span class="material-symbols-outlined">check_circle</span>
        <p class="text-sm font-medium">{{ $page.props.flash.success }}</p>
      </div>

      <!-- General Errors (like rejected login) -->
      <div v-if="form.errors.email"
        class="mb-6 flex items-center gap-3 p-4 bg-error-container text-on-error-container rounded-lg border border-error/20">
        <span class="material-symbols-outlined">error</span>
        <p class="text-sm font-medium">{{ form.errors.email }}</p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Email -->
        <div>
          <label class="block text-sm font-semibold mb-2 text-primary">Alamat Email</label>
          <input v-model="form.email" type="email"
            class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
            placeholder="contoh@sma4jember.sch.id" autocomplete="email" required />
        </div>

        <!-- Password -->
        <div class="relative">
          <label class="block text-sm font-semibold mb-2 text-primary">Kata Sandi</label>
          <input v-model="form.password" :type="showPass ? 'text' : 'password'"
            class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
            placeholder="••••••••" autocomplete="current-password" required />
          <button type="button" @click="showPass = !showPass"
            class="absolute right-4 top-10 text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined">{{ showPass ? 'visibility_off' : 'visibility' }}</span>
          </button>
          <p v-if="form.errors.password" class="text-error text-xs mt-1">{{ form.errors.password }}</p>
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.remember" type="checkbox"
              class="rounded text-primary focus:ring-primary border-outline-variant" />
            <span class="text-sm text-on-surface-variant">Ingat saya</span>
          </label>
          <Link :href="route('password.request')" class="text-sm font-bold text-primary hover:underline">
            Lupa Password?
          </Link>
        </div>

        <!-- Submit Button -->
        <button type="submit" :disabled="form.processing"
          class="w-full flex justify-center items-center gap-2 bg-primary text-white py-4 rounded-xl font-bold text-lg hover:bg-primary/90 transition-all active:scale-[0.98] shadow-lg shadow-primary/10 disabled:opacity-70">
          <span v-if="form.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ form.processing ? 'Masuk...' : 'Masuk' }}
        </button>
      </form>

      <!-- Bottom Links -->
      <div class="mt-8 pt-8 border-t border-outline-variant/30 text-center">
        <p class="text-on-surface-variant text-sm mb-4">Belum punya akun?</p>
        <div class="flex flex-col gap-3">
          <Link :href="route('register')"
            class="w-full block border border-primary text-primary py-3 rounded-xl font-bold hover:bg-secondary-container/30 transition-all text-center">
            Daftar Akun Baru
          </Link>
          <Link :href="route('claim.show')"
            class="w-full block bg-secondary-container text-primary py-3 rounded-xl font-bold hover:bg-secondary-container/70 transition-all text-center">
            Aktivasi Akun (Siswa/Guru)
          </Link>
        </div>
      </div>
    </div>
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const showPass = ref(false)

function submit() {
  form.post(route('login.submit'), {
    preserveScroll: true,
    onSuccess: () => form.reset('password'),
  })
}
</script>
