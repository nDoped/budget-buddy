<script setup lang=ts>
  import { ref, onMounted } from "vue";
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  const emit = defineEmits(['cancel']);
  const cancel = () => {
    emit('cancel');
  };

  // Camera
  const canvas = ref(null);
  const video = ref(null);
  const ctx = ref(null);
  const constraints = ref({
    video: {
      width: {
        min: 1280,
        ideal: 1920,
        max: 2560,
      },
      height: {
        min: 720,
        ideal: 1080,
        max: 1440
      },
      facingMode: 'environment',
    },
    audio: false,
  });
  onMounted(async () => {
    if ('mediaDevices' in navigator && 'getUserMedia' in navigator.mediaDevices) {
      await navigator.mediaDevices
        .getUserMedia(constraints.value)
        .then(SetStream)
        .catch((err) => {
          console.error("Error accessing the camera", err);
        });
      if (video.value && canvas.value) {
        video.value.onloadedmetadata = () => {
          if (canvas.value) {
            canvas.value.width = video.value.videoWidth;
            canvas.value.height = video.value.videoHeight;
          }
        };
        ctx.value = canvas.value.getContext("2d");
      }
    }
  });
  function SetStream(stream) {
    video.value.srcObject = stream;
    video.value.play();
    requestAnimationFrame(Draw);
  }
  function Draw() {
    if (video.value && canvas.value) {
      ctx.value.drawImage(video.value, 0, 0, canvas.value.width, canvas.value.height);
      requestAnimationFrame(Draw);
    }
  }

  // model
  const model = defineModel({
    type: String,
    default: "",
  });
  const base64Data = ref(null);
  const takePic = () => {
    let data = canvas.value.toDataURL('image/png', 1);
    base64Data.value = data;
  };
  const retakePic = () => {
    base64Data.value = null;
  };
  const savePic = () => {
    //let link = document.createElement("a");
    //link.download = `transaction-${new Date().toISOString()}.png`;
    //link.href = base64Data
    //link.click();
    model.value = base64Data
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
      hidden
    />

    <!--
      width="400"
      height="300"
      -->
    <canvas
      v-show="! base64Data"
      ref="canvas"
      width="video.videoWidth"
      height="video.videoHeight"
      style="object-fit: contain"
      class="bg-black rounded-3xl"
    />
    <img
      v-if="base64Data"
      :src="base64Data"
    >

    <div class="flex items-center justify-center py-4">
      <PrimaryButton
        v-if="! base64Data"
        ref="startButton"
        type="button"
        @click="takePic"
      >
        Take Pic
      </PrimaryButton>

      <template v-else>
        <PrimaryButton
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
  </div>
</template>
