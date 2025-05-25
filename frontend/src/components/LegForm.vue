<template>
  <q-card-section class="q-pa-sm bg-primary text-white">
    <div class="text-h6">
      {{ props.isEdit ? 'Edit' : 'Add' }} Leg for tour:
      <span class="text-orange">{{ form.tour_id }}</span>
    </div>
  </q-card-section>
  <q-card-section class="q-gutter-md">
    <!-- SimBrief Integration - Only show in add mode -->
    <div v-if="!props.isEdit" class="row q-gutter-sm q-mb-md">
      <q-btn
        color="secondary"
        icon="flight"
        label="SimBrief"
        @click="importFromSimBrief"
        :loading="simbriefLoading"
        no-caps
      />
    </div>

    <q-input
      v-model="form.origin"
      label="Origin ICAO"
      dense
      outlined
      required
      :rules="[(val) => (val && val.length > 0) || 'Origin is required']"
    />
    <q-input
      v-model="form.destination"
      label="Destination ICAO"
      dense
      outlined
      required
      :rules="[(val) => (val && val.length > 0) || 'Destination is required']"
    />
    <q-input
      v-model="form.aircraft"
      label="Aircraft ICAO"
      dense
      outlined
      required
      :rules="[(val) => (val && val.length > 0) || 'Aircraft is required']"
    />
    <q-input
      v-model="form.route"
      label="Route"
      dense
      outlined
      required
      type="textarea"
      :rows="2"
      :rules="[(val) => (val && val.length > 0) || 'Route is required']"
    />
    <q-input v-model="form.comments" label="Comments" dense outlined type="textarea" :rows="2" />
    <q-input v-model="form.link1" label="Link 1" dense outlined />
    <q-input v-model="form.link2" label="Link 2" dense outlined />
    <q-input v-model="form.link3" label="Link 3" dense outlined />
    <div class="column">
      <label class="text-subtitle2 q-mb-xs">Flight Date *</label>
      <q-date
        v-model="form.flight_date"
        mask="YYYY-MM-DD"
        dense
        flat
        bordered
        outlined
        minimal
        landscape
        class="q-mt-sm"
        :rules="[(val) => (val && val.length > 0) || 'Flight date is required']"
      />
    </div>
  </q-card-section>
  <q-card-actions align="right">
    <q-btn flat label="Cancel" class="bg-negative" @click="$emit('cancel')" :disable="loading" />
    <q-btn flat label="Save" class="bg-positive" @click="onSubmit" :loading="loading" />
  </q-card-actions>
</template>

<script setup>
import { reactive, watch, ref } from 'vue'
import { Notify } from 'quasar'
import { useFsToursStore } from '../stores/fstours'

const props = defineProps({
  modelValue: Object,
  loading: Boolean,
  isEdit: {
    type: Boolean,
    default: false,
  },
})
const emit = defineEmits(['update:modelValue', 'submit', 'cancel'])

const store = useFsToursStore()
const simbriefLoading = ref(false)

const form = reactive({
  tour_id: '',
  origin: '',
  destination: '',
  aircraft: '',
  route: '',
  comments: '',
  link1: '',
  link2: '',
  link3: '',
  flight_date: '',
})

watch(
  () => props.modelValue,
  (val) => {
    if (val) Object.assign(form, val)
  },
  { immediate: true },
)

watch(
  form,
  (val) => {
    emit('update:modelValue', { ...val })
  },
  { deep: true },
)

async function importFromSimBrief() {
  simbriefLoading.value = true

  try {
    const simbriefData = await store.fetchSimbriefData()

    // Map SimBrief data to form fields
    form.origin = simbriefData.origin
    form.destination = simbriefData.destination
    form.aircraft = simbriefData.aircraft
    form.route = simbriefData.route
    form.comments = simbriefData.comments

    // Add SimBrief link to first link field if empty
    if (!form.link1 && simbriefData.link) {
      form.link1 = simbriefData.link
    }
    Notify.create({
      type: 'positive',
      message: 'SimBrief data imported successfully!',
    })
  } catch (error) {
    console.error('SimBrief import error:', error)
    Notify.create({
      type: 'negative',
      message: error.message,
    })
  } finally {
    simbriefLoading.value = false
  }
}

function onSubmit() {
  // Validate all required fields
  const requiredFields = [
    { field: 'origin', name: 'Origin ICAO' },
    { field: 'destination', name: 'Destination ICAO' },
    { field: 'aircraft', name: 'Aircraft ICAO' },
    { field: 'route', name: 'Route' },
    { field: 'flight_date', name: 'Flight Date' },
  ]

  const missingFields = requiredFields.filter(
    ({ field }) => !form[field] || form[field].trim() === '',
  )

  if (missingFields.length > 0) {
    Notify.create({
      type: 'negative',
      message: `Please fill all required fields: ${missingFields.map((f) => f.name).join(', ')}`,
      position: 'top',
    })
    return
  }

  emit('submit')
}
</script>
