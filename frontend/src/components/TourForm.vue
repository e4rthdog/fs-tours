<template>
  <q-card flat bordered class="bg-dark text-white">
    <q-card-section class="q-pa-sm bg-primary text-white">
      <div class="text-h6">Edit Tour</div>
    </q-card-section>

    <q-card-section class="q-pa-md">
      <q-form @submit="handleSubmit" class="q-gutter-md">
        <!-- Tour ID (readonly) -->
        <q-input
          v-model="formData.tour_id"
          label="Tour ID"
          outlined
          readonly
          dense
          dark
          class="q-mb-md"
        />

        <!-- Tour Description -->
        <q-input
          v-model="formData.tour_description"
          label="Tour Description"
          outlined
          dense
          dark
          class="q-mb-md"
          :rules="[
            (val) => (val && val.length > 0) || 'Tour description is required',
            (val) =>
              (val && val.length <= 255) || 'Tour description must be 255 characters or less',
          ]"
        />

        <!-- Action Buttons -->
        <div class="row q-gutter-sm justify-end q-mt-md">
          <q-btn
            class="bg-negative"
            flat
            label="Cancel"
            @click="$emit('cancel')"
            :disable="loading"
          />
          <q-btn
            class="bg-positive"
            type="submit"
            label="Update Tour"
            color="primary"
            :loading="loading"
          />
        </div>
      </q-form>
    </q-card-section>
  </q-card>
</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      tour_id: '',
      tour_description: '',
    }),
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'submit', 'cancel'])

const formData = reactive({ ...props.modelValue })

// Only watch formData to emit updates
watch(
  formData,
  (newValue) => {
    emit('update:modelValue', { ...newValue })
  },
  { deep: true },
)

const handleSubmit = () => {
  emit('submit')
}
</script>
