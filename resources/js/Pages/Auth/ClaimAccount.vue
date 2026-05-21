<template>
  <AuthLayout>
    <div class="glass-card rounded-2xl p-8 md:p-10">
      
      <!-- Back button -->
      <Link v-if="!foundMember && step === 1" :href="route('login')" class="flex items-center gap-2 text-on-surface-variant hover:text-primary mb-6 transition-colors w-fit">
        <span class="material-symbols-outlined">arrow_back</span>
        <span class="text-sm font-medium">Kembali</span>
      </Link>

      <!-- ══════════════════════════════════════════════════ -->
      <!-- STEP 1: Lookup NIS/NIP                            -->
      <!-- ══════════════════════════════════════════════════ -->
      <div v-if="!foundMember">
        <h2 class="font-headline-md text-primary text-2xl mb-2">Aktivasi Akun Anggota</h2>
        <p class="text-on-surface-variant mb-8">Masukkan NIS atau NIP Anda untuk mengaktifkan akun.</p>
        
        <form @submit.prevent="submitLookup" class="space-y-6">
          <div>
            <label class="block text-sm font-semibold mb-2 text-primary">NIS / NIP</label>
            <input v-model="lookupForm.nis_nip" type="text"
              class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
              placeholder="Masukkan 10 digit nomor" autofocus required />
            <p v-if="lookupForm.errors.nis_nip" class="text-error text-xs mt-1">{{ lookupForm.errors.nis_nip }}</p>
          </div>

          <div class="bg-primary-container/10 border border-primary-container/20 p-4 rounded-xl flex items-start gap-3">
            <span class="material-symbols-outlined text-primary mt-0.5">info</span>
            <p class="text-xs text-on-primary-container leading-relaxed">
              Data NIS/NIP harus diinput oleh admin terlebih dahulu. Jika tidak ditemukan, hubungi petugas perpustakaan.
            </p>
          </div>

          <button type="submit" :disabled="lookupForm.processing"
            class="w-full flex justify-center items-center gap-2 bg-primary text-white py-4 rounded-xl font-bold hover:bg-primary/90 transition-all shadow-lg shadow-primary/10 disabled:opacity-70">
            <span v-if="lookupForm.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ lookupForm.processing ? 'Mencari...' : 'Cari Data Anggota' }}
          </button>
        </form>
      </div>

      <!-- ══════════════════════════════════════════════════ -->
      <!-- STEP 2: Set Email & Password → kirim OTP Email    -->
      <!-- ══════════════════════════════════════════════════ -->
      <div v-if="foundMember && step === 2">
        <div class="flex items-center justify-between mb-6">
          <h2 class="font-headline-md text-primary text-2xl">Konfirmasi Data</h2>
          <button @click="resetLookup" class="text-sm font-semibold text-error hover:underline">Ganti</button>
        </div>

        <!-- Glassmorphism Profile Card -->
        <div class="bg-secondary-container/20 rounded-2xl p-6 border border-primary/10 mb-8 backdrop-blur-sm">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-primary flex items-center justify-center text-white text-2xl font-bold uppercase shadow-inner">
              {{ foundMember.name[0] }}
            </div>
            <div>
              <p class="font-bold text-primary text-lg leading-tight">{{ foundMember.name }}</p>
              <p class="text-on-surface-variant text-sm">{{ foundMember.type === 'siswa' ? 'NIS' : 'NIP' }}: {{ foundMember.nis_nip }}</p>
              <span class="inline-block mt-2 px-3 py-1 bg-primary/10 text-primary text-[10px] uppercase tracking-widest font-bold rounded-full">
                {{ foundMember.type === 'siswa' ? 'Siswa Aktif' : 'Guru/Karyawan' }}
              </span>
            </div>
          </div>
        </div>

        <form @submit.prevent="submitActivate" class="space-y-5">
          <!-- Email Baru -->
          <div>
            <label class="block text-sm font-semibold mb-1 text-primary">Email Baru <span class="text-error">*</span></label>
            <input v-model="activateForm.email" type="email"
              class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
              placeholder="email@contoh.com" autofocus required />
            <p v-if="activateForm.errors.email" class="text-error text-xs mt-1">{{ activateForm.errors.email }}</p>
          </div>

          <!-- Password Baru -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="relative">
              <label class="block text-sm font-semibold mb-1 text-primary">Buat Kata Sandi <span class="text-error">*</span></label>
              <input v-model="activateForm.password" :type="showPass ? 'text' : 'password'"
                class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                placeholder="Min. 8 karakter" required />
              <button type="button" @click="showPass = !showPass" class="absolute right-4 top-9 text-on-surface-variant hover:text-primary">
                <span class="material-symbols-outlined">{{ showPass ? 'visibility_off' : 'visibility' }}</span>
              </button>
              <p v-if="activateForm.errors.password" class="text-error text-xs mt-1">{{ activateForm.errors.password }}</p>
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1 text-primary">Konfirmasi <span class="text-error">*</span></label>
              <input v-model="activateForm.password_confirmation" :type="showPass ? 'text' : 'password'"
                class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                placeholder="Ulangi kata sandi" required />
            </div>
          </div>

          <div class="bg-primary-container/10 border border-primary-container/20 p-4 rounded-xl flex items-start gap-3 mt-2">
            <span class="material-symbols-outlined text-primary mt-0.5">mark_email_unread</span>
            <p class="text-xs text-on-primary-container leading-relaxed">
              Kode aktivasi akan dikirimkan ke alamat email di atas.
            </p>
          </div>

          <button type="submit" :disabled="activateForm.processing"
            class="w-full flex justify-center items-center gap-2 bg-primary text-white py-4 rounded-xl font-bold mt-4 hover:bg-primary/90 transition-all shadow-lg shadow-primary/10 disabled:opacity-70">
            <span v-if="activateForm.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ activateForm.processing ? 'Mengirim...' : 'Kirim Kode Aktivasi' }}
          </button>
        </form>
      </div>

      <!-- ══════════════════════════════════════════════════ -->
      <!-- STEP 3: Verifikasi OTP Email                      -->
      <!-- ══════════════════════════════════════════════════ -->
      <div v-if="step === 3">
        <div class="mb-8 text-center">
          <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center bg-primary-container/10 border border-primary-container/20">
            <span class="material-symbols-outlined text-primary text-3xl">mark_email_read</span>
          </div>
          <h2 class="font-headline-md text-primary text-2xl mb-2">Verifikasi Akhir</h2>
          <p class="text-on-surface-variant">
            Masukkan 6 digit kode yang dikirim ke <br>
            <span class="font-bold text-primary">{{ otpTargetEmail }}</span>
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

          <div class="text-center">
            <p class="text-sm text-on-surface-variant mb-1">Kode berlaku 10 menit. Cek folder Spam jika tidak masuk.</p>
          </div>

          <button type="submit" :disabled="otpForm.processing || otpCode.length < 6"
            class="w-full flex justify-center items-center gap-2 bg-primary text-white py-4 rounded-xl font-bold text-lg hover:bg-primary/90 transition-all shadow-lg shadow-primary/10 disabled:opacity-70">
            <span v-if="otpForm.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ otpForm.processing ? 'Memverifikasi...' : 'Aktivasi Sekarang' }}
          </button>
          
          <div class="flex flex-col gap-3 mt-4">
            <button type="button" @click="resendOtp" :disabled="resending"
              class="w-full text-primary font-bold text-sm hover:underline disabled:opacity-50">
              {{ resending ? 'Mengirim...' : 'Kirim Ulang Kode' }}
            </button>
            <button type="button" @click="step = 2"
              class="w-full text-outline font-medium text-sm hover:text-primary transition-colors">
              &larr; Ubah email / kata sandi
            </button>
          </div>
        </form>
      </div>

    </div>
  </AuthLayout>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

const props = defineProps({
  foundMember: { type: Object, default: null },
  otpSent:     { type: Boolean, default: false },
  otpEmail:    { type: String, default: null },
})

// Inisialisasi step dari server props
const step = ref(
  props.otpSent ? 3 : props.foundMember ? 2 : 1
)
const showPass = ref(false)
const otpTargetEmail = ref(props.otpEmail ?? '')
const resending = ref(false)

// Watch props saat Inertia update tanpa remount
watch(() => props.foundMember, (val) => {
  if (val && step.value === 1) step.value = 2
})
watch(() => props.otpSent, (val) => {
  if (val) {
    otpTargetEmail.value = props.otpEmail ?? ''
    otpForm.member_id = props.foundMember?.id ?? ''
    otpForm.email = props.otpEmail ?? ''
    step.value = 3
    nextTick(() => { if (otpRefs.value[0]) otpRefs.value[0].focus() })
  }
})

// ── Step 1: Lookup ─────────────────────────────────────────
const lookupForm = useForm({ nis_nip: '' })

function submitLookup() {
  lookupForm.post(route('claim.lookup'))
}

function resetLookup() {
  // Hapus session foundMember di server dulu
  router.post(route('claim.reset'), {}, {
    preserveState: false,
    onFinish: () => router.get(route('claim.show')),
  })
}

// ── Step 2: Email + Password → kirim OTP ──────────────────
const activateForm = useForm({
  member_id: props.foundMember?.id ?? '',
  email: '',
  password: '',
  password_confirmation: '',
})

if (props.foundMember) {
  activateForm.member_id = props.foundMember.id
}

function submitActivate() {
  activateForm.member_id = props.foundMember.id
  // Server menyimpan OTP state di session lalu redirect ke claim.show
  // Inertia redirect otomatis memperbarui props → watch otpSent menangani transisi ke step 3
  activateForm.post(route('claim.send-otp'), {
    preserveScroll: true,
  })
}

// ── Step 3: OTP ────────────────────────────────────────────
const otpDigits = ref(['', '', '', '', '', ''])
const otpRefs = ref([])
const otpForm = useForm({ member_id: '', email: '', otp: '' })

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
  otpForm.post(route('claim.verify-otp'), {
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
  activateForm.post(route('claim.send-otp'), {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => { resending.value = false },
    onSuccess: () => {
      otpDigits.value = ['', '', '', '', '', '']
      nextTick(() => otpRefs.value[0]?.focus())
    },
  })
}
</script>
