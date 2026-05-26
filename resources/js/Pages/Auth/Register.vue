<template>
  <AuthLayout>
    <div class="glass-card rounded-2xl p-8 md:p-10">
      
      <!-- ══════════════════════════════════════════════════ -->
      <!-- STEP 1: Form Pendaftaran                           -->
      <!-- ══════════════════════════════════════════════════ -->
      <div v-if="step === 1">
        <Link :href="route('login')" class="flex items-center gap-2 text-on-surface-variant hover:text-primary mb-6 transition-colors w-fit">
          <span class="material-symbols-outlined">arrow_back</span>
          <span class="text-sm font-medium">Kembali</span>
        </Link>
        
        <h2 class="font-headline-md text-primary text-2xl mb-2">Daftar Akun</h2>
        <p class="text-on-surface-variant mb-8">Lengkapi data diri Anda untuk memulai.</p>
        
        <div class="bg-primary-container/10 border border-primary-container/20 p-4 rounded-xl flex items-start gap-3 mb-8">
          <span class="material-symbols-outlined text-primary mt-0.5">info</span>
          <p class="text-xs text-on-primary-container leading-relaxed">
            Kode OTP akan dikirim ke <strong>Email</strong> yang Anda masukkan. Pastikan email aktif.
          </p>
        </div>

        <form @submit.prevent="submitRegister" class="space-y-5">
          <!-- Nama -->
          <div>
            <label class="block text-sm font-semibold mb-1 text-primary">Nama Lengkap <span class="text-error">*</span></label>
            <input v-model="form.name" type="text"
              class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
              placeholder="John Doe" autocomplete="name" required />
            <p v-if="form.errors.name" class="text-error text-xs mt-1">{{ form.errors.name }}</p>
          </div>

          <!-- Email & WA -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold mb-1 text-primary">Alamat Email <span class="text-error">*</span></label>
              <input v-model="form.email" type="email"
                class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                placeholder="email@contoh.com" autocomplete="email" required />
              <p v-if="form.errors.email" class="text-error text-xs mt-1">{{ form.errors.email }}</p>
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1 text-primary">No. WhatsApp <span class="text-outline font-normal">(Opsional)</span></label>
              <input v-model="form.phone" type="tel"
                class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                placeholder="081234567890" />
              <p v-if="form.errors.phone" class="text-error text-xs mt-1">{{ form.errors.phone }}</p>
            </div>
          </div>

          <!-- Password & Konfirmasi -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="relative">
              <label class="block text-sm font-semibold mb-1 text-primary">Kata Sandi <span class="text-error">*</span></label>
              <input v-model="form.password" :type="showPass ? 'text' : 'password'"
                class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                placeholder="••••••••" autocomplete="new-password" required />
              <button type="button" @click="showPass = !showPass" class="absolute right-4 top-9 text-on-surface-variant hover:text-primary">
                <span class="material-symbols-outlined">{{ showPass ? 'visibility_off' : 'visibility' }}</span>
              </button>
              <p v-if="form.errors.password" class="text-error text-xs mt-1">{{ form.errors.password }}</p>
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1 text-primary">Konfirmasi <span class="text-error">*</span></label>
              <input v-model="form.password_confirmation" :type="showPass ? 'text' : 'password'"
                class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                placeholder="Ulangi password" autocomplete="new-password" required />
            </div>
          </div>

          <button type="submit" :disabled="form.processing"
            class="w-full flex justify-center items-center gap-2 bg-primary text-white py-4 rounded-xl font-bold mt-4 hover:bg-primary/90 transition-all shadow-lg shadow-primary/10 disabled:opacity-70">
            <span v-if="form.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ form.processing ? 'Mengirim OTP...' : 'Daftar & Kirim OTP' }}
          </button>
        </form>
      </div>

      <!-- ══════════════════════════════════════════════════ -->
      <!-- STEP 2: Verifikasi OTP Email                       -->
      <!-- ══════════════════════════════════════════════════ -->
      <div v-else>
        <div class="mb-8 text-center">
          <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center bg-primary-container/10 border border-primary-container/20">
            <span class="material-symbols-outlined text-primary text-3xl">mark_email_read</span>
          </div>
          <h2 class="font-headline-md text-primary text-2xl mb-2">Verifikasi Email</h2>
          <p class="text-on-surface-variant">
            Masukkan 6 digit kode yang dikirim ke <br>
            <span class="font-bold text-primary">{{ emailHint }}</span>
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
            <div v-if="Object.keys(form.errors).length > 0" class="mt-3 bg-error/10 border border-error/20 p-3 rounded-xl">
              <p class="text-xs text-error text-center font-medium">
                {{ Object.values(form.errors)[0] }}
              </p>
            </div>
          </div>

          <div class="text-center">
            <p class="text-sm text-on-surface-variant mb-1">Kode berlaku 10 menit.</p>
          </div>

          <button type="submit" :disabled="otpForm.processing || otpCode.length < 6"
            class="w-full flex justify-center items-center gap-2 bg-primary text-white py-4 rounded-xl font-bold text-lg hover:bg-primary/90 transition-all shadow-lg shadow-primary/10 disabled:opacity-70">
            <span v-if="otpForm.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ otpForm.processing ? 'Memverifikasi...' : 'Verifikasi Akun' }}
          </button>
          
          <div class="flex flex-col gap-3 mt-4">
            <button type="button" @click="resendOtp" :disabled="resending"
              class="w-full text-primary font-bold text-sm hover:underline disabled:opacity-50">
              {{ resending ? 'Mengirim...' : 'Kirim Ulang Kode' }}
            </button>
            <button type="button" @click="step = 1"
              class="w-full text-outline font-medium text-sm hover:text-primary transition-colors">
              &larr; Ubah data pendaftaran
            </button>
          </div>
        </form>
      </div>

    </div>
  </AuthLayout>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

const props = defineProps({
  flash: { type: Object, default: () => ({}) },
})

const step = ref(1)
const showPass = ref(false)
const emailHint = ref('')
const resending = ref(false)

// ── Step 1: Register Form ────────────────────────────────────
const form = useForm({
  name: '', email: '', phone: '',
  password: '', password_confirmation: '',
})

function submitRegister() {
  form.post(route('register.send-otp'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      if (page.props.flash?.otp_sent) {
        emailHint.value = page.props.flash.email_hint ?? ''
        otpForm.email = form.email
        step.value = 2
        nextTick(() => { if (otpRefs.value[0]) otpRefs.value[0].focus() })
      }
    },
  })
}

// ── Step 2: OTP Form ─────────────────────────────────────────
const otpDigits = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
const otpForm = useForm({ email: '', otp: '' })

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
  otpForm.post(route('register.verify-otp'), {
    preserveState: true,
    preserveScroll: true,
    onError: () => {
      otpDigits.value = ['', '', '', '', '', '']
      nextTick(() => otpRefs.value[0]?.focus())
    },
  })
}

async function resendOtp() {
  resending.value = true
  form.post(route('register.send-otp'), {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => { resending.value = false },
    onSuccess: (page) => {
      if (page.props.flash?.otp_sent) {
        otpDigits.value = ['', '', '', '', '', '']
        nextTick(() => otpRefs.value[0]?.focus())
      }
    },
  })
}
</script>
