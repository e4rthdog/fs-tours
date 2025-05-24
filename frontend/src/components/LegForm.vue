<template>
  <q-card-section class="q-pa-sm bg-primary text-white">
    <div class="text-h6">
      Edit Leg for tour: <span class="text-orange">{{ form.tour_id }}</span>
    </div>
  </q-card-section>
  <q-card-section class="q-gutter-md">
    <q-input v-model="form.origin" label="Origin ICAO" dense outlined required />
    <q-input v-model="form.destination" label="Destination ICAO" dense outlined required />
    <q-input v-model="form.aircraft" label="Aircraft ICAO" dense outlined />
    <q-input v-model="form.route" label="Route" dense outlined type="textarea" :rows="2" />
    <q-input v-model="form.comments" label="Comments" dense outlined type="textarea" :rows="2" />
    <q-input v-model="form.link1" label="Link 1" dense outlined />
    <q-input v-model="form.link2" label="Link 2" dense outlined />
    <q-input v-model="form.link3" label="Link 3" dense outlined />
    <div class="column">
      <label class="text-subtitle2 q-mb-xs">Flight Date</label>
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
      />
    </div>
  </q-card-section>
  <q-card-actions align="right">
    <q-btn flat label="Cancel" color="grey" @click="$emit('cancel')" :disable="loading" />
    <q-btn flat label="Save" color="positive" @click="onSubmit" :loading="loading" />
  </q-card-actions>
</template>

<script setup>
import { reactive, watch } from 'vue'
const props = defineProps({
  modelValue: Object,
  loading: Boolean,
})
const emit = defineEmits(['update:modelValue', 'submit', 'cancel'])

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

function onSubmit() {
  emit('submit')
}
</script>
