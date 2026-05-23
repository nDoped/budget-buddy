<script setup lang="ts">
  import { ref, onMounted, onBeforeUnmount, computed } from "vue";
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import TextInput from '@/Components/TextInput.vue';
  import { focusElement, randomUUID } from '@/lib.js';
  const emit = defineEmits(['cancel', 'update:modelValue']);

  const canvas = ref<HTMLCanvasElement | null>(null);
  const video = ref<HTMLVideoElement | null>(null);
  let mediaStream: MediaStream | null = null;
  const torchOn = ref(false);
  const facingMode = ref<'environment' | 'user'>('environment');
  const torchSupported = ref(false);
  const isMobile = /Mobi|Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

  const constraints = computed(() => ({
    video: {
      width: { ideal: 1920, max: 3840 },
      height: { ideal: 1080, max: 2160 },
      facingMode: facingMode.value,
    },
    audio: false,
  }));

  function stopStream() {
    if (mediaStream) {
      mediaStream.getTracks().forEach(t => t.stop());
      mediaStream = null;
    }
    torchOn.value = false;
  }

  async function startCamera() {
    if (!('mediaDevices' in navigator && 'getUserMedia' in navigator.mediaDevices)) return;
    stopStream();
    try {
      const s = await navigator.mediaDevices.getUserMedia(constraints.value);
      mediaStream = s;
      if (video.value) {
        video.value.srcObject = s;
      }
      const [track] = s.getVideoTracks();
      torchSupported.value = !!track?.getCapabilities?.()?.torch;
      if (!torchSupported.value && typeof ImageCapture !== 'undefined') {
        try {
          const capture = new ImageCapture(track);
          const caps = await capture.getPhotoCapabilities();
          torchSupported.value = caps?.torch?.some?.((v: boolean) => v === true) ?? false;
        } catch {
          /* ImageCapture API not available */
        }
      }
    } catch (err) {
      console.error("Error accessing the camera", err);
    }
  }

  async function toggleCamera() {
    facingMode.value = facingMode.value === 'environment' ? 'user' : 'environment';
    await startCamera();
  }

  async function toggleTorch() {
    if (!mediaStream) return;
    const [track] = mediaStream.getVideoTracks();
    if (!track) return;
    const newState = !torchOn.value;
    try {
      await track.applyConstraints({ advanced: [{ torch: newState }] });
      torchOn.value = newState;
      return;
    } catch {
      /* fall through to ImageCapture */
    }
    if (typeof ImageCapture !== 'undefined') {
      try {
        const capture = new ImageCapture(track);
        await capture.setTorch(newState);
        torchOn.value = newState;
      } catch (err) {
        console.error("Torch not supported", err);
      }
    }
  }

  onMounted(async () => {
    await startCamera();
    focusElement(getUuid('take-pic-btn'));
  });

  onBeforeUnmount(() => {
    stopStream();
  });

  const cancel = () => {
    stopStream();
    emit('cancel');
  };

  const model = defineModel({
    type: Object,
    default: () => ({ base64: "", name: "" }),
  });

  const base64 = ref<string | null>(null);
  const name = ref("");

  const takePic = () => {
    if (!canvas.value || !video.value || !video.value.videoWidth) return;
    canvas.value.width = video.value.videoWidth;
    canvas.value.height = video.value.videoHeight;
    const ctx = canvas.value.getContext("2d");
    if (!ctx) return;
    ctx.imageSmoothingEnabled = false;
    ctx.drawImage(video.value, 0, 0);
    base64.value = canvas.value.toDataURL('image/jpeg', 0.92);
    focusElement(getUuid('file-name'));
  };

  const retakePic = () => {
    base64.value = null;
    focusElement(getUuid('take-pic-btn'));
  };

  const savePic = () => {
    model.value = { base64: base64.value, name: name.value };
  };
  const uuid = randomUUID();
  const getUuid = (el, i = 0) => {
    return `${el}-${i}-${uuid}`;
  };
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center">
    <video
      ref="video"
      autoplay
      playsinline
      webkit-playsinline
      muted
      class="bg-black rounded-3xl w-full max-w-lg"
      :class="{ hidden: !!base64 }"
    />

    <img
      v-if="base64"
      :src="base64"
      class="bg-black rounded-3xl w-full max-w-lg"
    >

    <canvas ref="canvas" class="hidden" />

    <div class="flex items-center justify-center gap-2 py-4 flex-wrap">
      <SecondaryButton
        v-if="isMobile && !base64"
        type="button"
        @click="toggleCamera"
      >
        Switch Camera
      </SecondaryButton>

      <SecondaryButton
        v-if="facingMode === 'environment' && torchSupported && !base64"
        type="button"
        @click="toggleTorch"
      >
        {{ torchOn ? 'Flashlight Off' : 'Flashlight On' }}
      </SecondaryButton>

      <PrimaryButton
        v-if="!base64"
        :id="getUuid('take-pic-btn')"
        type="button"
        @click="takePic"
      >
        Take Pic
      </PrimaryButton>

      <template v-else>
        <PrimaryButton
          :id="getUuid('save-pic-btn')"
          type="button"
          @click="savePic"
        >
          Save Pic
        </PrimaryButton>

        <SecondaryButton
          type="button"
          @click="retakePic"
        >
          Retake Pic
        </SecondaryButton>
      </template>

      <SecondaryButton
        type="button"
        @click="cancel"
      >
        Cancel
      </SecondaryButton>
    </div>

    <div v-if="base64" class="w-full max-w-lg">
      <InputLabel
        :for="getUuid('file-name')"
        value="File Name"
      />
      <TextInput
        :id="getUuid('file-name')"
        v-model="name"
        label="File Name"
        type="text"
        @keydown.enter="savePic"
        class="mt-2"
      />
    </div>
  </div>
</template>
