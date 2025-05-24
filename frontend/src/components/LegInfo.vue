<template>
  <div class="q-pa-sm bg-dark text-white">
    <div class="flex items-center justify-between">
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

    <q-table
      class="q-mt-sm bg-transparent text-white"
      :rows="legDetailsToRows(leg)"
      :columns="legDetailsColumns"
      hide-bottom
      hide-header
      dense
      flat
      dark
      :pagination="{ rowsPerPage: 0 }"
      separator="horizontal"
    />

    <!-- Link buttons -->
    <div class="q-mt-sm q-gutter-sm row">
      <q-btn
        v-if="leg.link1"
        size="sm"
        color="warning"
        icon="launch"
        :href="leg.link1"
        target="_blank"
        label="Link 1"
        outline
      />
      <q-btn
        v-if="leg.link2"
        size="sm"
        color="warning"
        icon="launch"
        :href="leg.link2"
        target="_blank"
        label="Link 2"
        outline
      />
      <q-btn
        v-if="leg.link3"
        size="sm"
        color="warning"
        icon="launch"
        :href="leg.link3"
        target="_blank"
        label="Link 3"
        outline
      />
    </div>
  </div>
</template>

<script setup>
defineProps({
  leg: Object,
  loading: Boolean,
  legDetailsToRows: Function,
  legDetailsColumns: Array,
})
</script>
