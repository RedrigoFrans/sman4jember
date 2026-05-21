<template>
  <AuthLayout>
    <div class="glass-card rounded-2xl p-8 md:p-10">

      <!-- STEP 1: Email Form -->
      <div v-if="step === 1">
        <Link :href="route('login')" class="flex items-center gap-2 text-on-surface-variant hover:text-primary mb-6 transition-colors w-fit">
          <span class="material-symbols-outlined">arrow_back</span>
          <span class="text-sm font-medium">Batal</span>
        </Link>
        <h2 class="font-headline-md text-primary text-2xl mb-2">Lupa Kata Sandi?</h2>
        <p class="text-on-surface-variant mb-8">Masukkan alamat email Anda yang terdaftar, kami akan mengirimkan instruksi untuk mereset password.</p>

        <form @submit.prevent="submitEmail" class="space-y-6">
          <div>
            <label class="block text-sm font-semibold mb-2 text-primary">Email Pemulihan</label>
            <input v-model="emailForm.email" type="email"
              class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
              placeholder="nama@email.com" required autofocus />
            <p v-if="emailForm.errors.email" class="text-error text-xs mt-1">{{ emailForm.errors.email }}</p>
          </div>

          <button type="submit" :disabled="emailForm.processing"
            class="w-full flex items-center justify-center gap-2 bg-primary text-white py-4 rounded-xl font-bold hover:shadow-lg transition-all disabled:opacity-70">
            <span v-if="emailForm.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ emailForm.processing ? 'Mengirim...' : 'Kirim Link / OTP Reset' }}
          </button>
        </form>

        <div class="mt-8 pt-6 border-t border-outline-variant/30 text-center">
          <Link :href="route('login')" class="text-sm font-bold text-primary flex items-center justify-center gap-2 hover:underline">
            Kembali ke Login
          </Link>
        </div>
      </div>

      <!-- STEP 2: OTP Form -->
      <div v-if="step === 2">
        <div class="mb-8 text-center">
          <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center bg-primary-container/10 border border-primary-container/20">
            <span class="material-symbols-outlined text-primary text-3xl">mark_email_read</span>
          </div>
          <h2 class="font-headline-md text-primary text-2xl mb-2">Verifikasi OTP</h2>
          <p class="text-on-surface-variant">
            Masukkan 6 digit kode yang dikirim ke <br>
            <span class="font-bold text-primary">{{ emailForm.email }}</span>
          </p>
        </div>

        <form @submit.prevent="submitOtp" class="space-y-6">
          <div>
            <div class="flex justify-between gap-2 md:gap-3 mb-2">
              <input
                v-for="(_, i) in otpDigits"
                :key="i"
                :ref="el => { if (el) otpRefs[i] = el }"
                v-model="otpDigits[i]"
                type="text"
                inputmode="numeric"
                maxlength="1"
                class="w-12 h-14 md:w-14 md:h-16 text-center text-2xl font-bold rounded-xl border-2 transition-all bg-white text-primary"
                :class="otpDigits[i] ? 'border-primary ring-2 ring-primary/20' : 'border-outline-variant focus:border-primary focus:ring-0'"
                @input="onOtpInput(i, $event)"
                @keydown="onOtpKeydown(i, $event)"
                @paste="onOtpPaste($event)"
              />
            </div>
            <p v-if="otpForm.errors.otp" class="text-error text-xs text-center mt-2">{{ otpForm.errors.otp }}</p>
          </div>

          <button type="submit" :disabled="otpForm.processing || otpCode.length < 6"
            class="w-full flex items-center justify-center gap-2 bg-primary text-white py-4 rounded-xl font-bold hover:shadow-lg transition-all disabled:opacity-70">
            <span v-if="otpForm.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ otpForm.processing ? 'Memverifikasi...' : 'Verifikasi OTP' }}
          </button>
          
          <div class="mt-4 text-center">
            <button type="button" @click="step = 1" class="text-sm font-medium text-outline hover:text-primary transition-colors">
              Bukan email Anda? Ganti email
            </button>
          </div>
        </form>
      </div>

      <!-- STEP 3: Reset Password Form -->
      <div v-if="step === 3">
        <div class="mb-8 text-center">
          <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center bg-primary-container/10 border border-primary-container/20">
            <span class="material-symbols-outlined text-primary text-3xl">lock_reset</span>
          </div>
          <h2 class="font-headline-md text-primary text-2xl mb-2">Buat Kata Sandi Baru</h2>
          <p class="text-on-surface-variant">Silakan buat passsword baru untuk akun Anda agar dapat login kembali.</p>
        </div>

        <form @submit.prevent="submitReset" class="space-y-5">
          <div class="relative">
            <label class="block text-sm font-semibold mb-2 text-primary">Kata Sandi Baru</label>
            <input v-model="resetForm.password" :type="showPass ? 'text' : 'password'"
              class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
              placeholder="Min. 8 karakter" required autofocus />
            <button type="button" @click="showPass = !showPass" class="absolute right-4 top-10 text-on-surface-variant hover:text-primary">
              <span class="material-symbols-outlined">{{ showPass ? 'visibility_off' : 'visibility' }}</span>
            </button>
            <p v-if="resetForm.errors.password" class="text-error text-xs mt-1">{{ resetForm.errors.password }}</p>
          </div>

          <div class="relative">
            <label class="block text-sm font-semibold mb-2 text-primary">Konfirmasi Kata Sandi Baru</label>
            <input v-model="resetForm.password_confirmation" :type="showPass ? 'text' : 'password'"
              class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
              placeholder="Ulangi kata sandi" required />
            <p v-if="resetForm.errors.password_confirmation" class="text-error text-xs mt-1">{{ resetForm.errors.password_confirmation }}</p>
          </div>

          <button type="submit" :disabled="resetForm.processing"
            class="w-full flex items-center justify-center gap-2 bg-primary text-white py-4 rounded-xl font-bold mt-4 hover:shadow-lg transition-all disabled:opacity-70">
            <span v-if="resetForm.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ resetForm.processing ? 'Menyimpan...' : 'Atur Ulang Kata Sandi' }}
          </button>
        </form>
      </div>

    </div>
  </AuthLayout>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

const step = ref(1)
const showPass = ref(false)

// ── Step 1: Email Form ───────────────────────────────────────
const emailForm = useForm({
  email: ''
})

function submitEmail() {
  emailForm.post(route('password.email'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      // Transition to OTP step on success
      if (page.props.flash?.success && !Object.keys(emailForm.errors).length) {
        step.value = 2;
        otpForm.email = emailForm.email;
        resetForm.email = emailForm.email;
        nextTick(() => { if (otpRefs.value[0]) otpRefs.value[0].focus() })
      }
    }
  })
}

// ── Step 2: OTP Form ─────────────────────────────────────────
const otpDigits = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
const otpForm = useForm({
  email: '',
  otp: ''
})

const otpCode = computed(() => otpDigits.value.join(''))

function onOtpInput(index, event) {
  const val = event.target.value.replace(/\D/g, '')
  otpDigits.value[index] = val ? val[val.length - 1] : ''
  if (val && index < 5) nextTick(() => otpRefs.value[index + 1]?.focus())
}

function onOtpKeydown(index, event) {
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    nextTick(() => otpRefs.value[index - 1]?.focus())
  }
}

function onOtpPaste(event) {
  const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6)
  if (!pasted) return
  event.preventDefault()
  pasted.split('').forEach((char, i) => { otpDigits.value[i] = char })
  nextTick(() => otpRefs.value[Math.min(pasted.length, 5)]?.focus())
}

function submitOtp() {
  otpForm.otp = otpCode.value
  otpForm.post(route('password.verify'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      // Transition to Update Password step if token is provided
      if (page.props.flash?.reset_token && !Object.keys(otpForm.errors).length) {
        resetForm.reset_token = page.props.flash.reset_token;
        step.value = 3;
      }
    },
    onError: () => {
      otpDigits.value = ['', '', '', '', '', '']
      nextTick(() => otpRefs.value[0]?.focus())
    }
  })
}

// ── Step 3: Reset Password ───────────────────────────────────
const resetForm = useForm({
  email: '',
  reset_token: '',
  password: '',
  password_confirmation: ''
})

function submitReset() {
  resetForm.post(route('password.update'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Usually redirects away.
      resetForm.reset('password', 'password_confirmation');
    }
  })
}
</script>
