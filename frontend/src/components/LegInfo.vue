<template>
  <q-card flat class="bg-dark text-white q-pa-sm" style="min-width: 320px; max-width: 95vw">
    <q-card-section class="q-pa-sm bg-primary text-white">
      <div class="text-h6">
        <div class="text-h6">{{ leg.origin }} -> {{ leg.destination }}</div>
        <div class="q-gutter-x-sm">
          <q-btn flat round dense icon="edit" title="Edit Leg" @click="$emit('edit', leg)" />
          <q-btn
            flat
            round
            dense
            icon="delete"
            title="Delete Leg"
            @click="$emit('delete', leg)"
            :loading="loading"
          />
        </div>
      </div>
    </q-card-section>
    <q-card-section class="q-pa-none">
      <div class="flex items-center justify-between q-mb-sm"></div>
      <table class="bg-transparent text-white q-mt-sm" style="width: 100%">
        <tbody>
          <tr>
            <td class="text-left q-pa-xs text-grey-4" style="min-width: 120px">Tour ID</td>
            <td class="text-left q-pa-xs">{{ leg.tour_id }}</td>
          </tr>
          <tr>
            <td class="text-left q-pa-xs text-grey-4">Sequence</td>
            <td class="text-left q-pa-xs">{{ leg.sequence }}</td>
          </tr>
          <tr>
            <td class="text-left q-pa-xs text-grey-4">Origin</td>
            <td class="text-left q-pa-xs">{{ leg.origin }}</td>
          </tr>
          <tr>
            <td class="text-left q-pa-xs text-grey-4">Destination</td>
            <td class="text-left q-pa-xs">{{ leg.destination }}</td>
          </tr>
          <tr>
            <td class="text-left q-pa-xs text-grey-4">Aircraft</td>
            <td class="text-left q-pa-xs">{{ leg.aircraft }}</td>
          </tr>
          <tr>
            <td class="text-left q-pa-xs text-grey-4">Route</td>
            <td class="text-left q-pa-xs">{{ leg.route }}</td>
          </tr>
          <tr>
            <td class="text-left q-pa-xs text-grey-4">Date</td>
            <td class="text-left q-pa-xs">{{ formatDate(leg.flight_date) }}</td>
          </tr>
          <tr v-if="leg.comments">
            <td class="text-left q-pa-xs text-grey-4">Comments</td>
            <td class="text-left q-pa-xs">{{ leg.comments }}</td>
          </tr>
        </tbody>
      </table>
      <div class="q-mt-sm q-gutter-sm row">
        <q-btn
          v-if="leg.link1"
          size="sm"
          color="secondary"
          icon="launch"
          :href="leg.link1"
          target="_blank"
          label="Link 1"
          outline
        />
        <q-btn
          v-if="leg.link2"
          size="sm"
          color="secondary"
          icon="launch"
          :href="leg.link2"
          target="_blank"
          label="Link 2"
          outline
        />
        <q-btn
          v-if="leg.link3"
          size="sm"
          color="secondary"
          icon="launch"
          :href="leg.link3"
          target="_blank"
          label="Link 3"
          outline
        />
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup>
defineProps({
  leg: Object,
  loading: Boolean,
})

const formatDate = (dateString) => {
  if (!dateString) return ''

  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('el')
  } catch {
    return dateString
  }
}
</script>
