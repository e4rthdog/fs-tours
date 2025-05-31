<template>
  <div>
    <!-- Logo and Title -->
    <div v-if="showLogoTitle" class="row items-center">
      <q-avatar square size="32px" class="q-mr-md">
        <img src="icons/fs-tours.png" alt="Logo" />
      </q-avatar>
      <q-toolbar-title shrink :class="titleClass">FS Tours</q-toolbar-title>
    </div>

    <!-- Tour Select -->
    <q-select
      v-if="showSelect"
      v-model="store.selectedTour"
      :options="tourOptions"
      option-value="value"
      option-label="label"
      label="Select Tour"
      dense
      outlined
      :style="selectStyle"
      :loading="store.loading"
      @update:model-value="onTourSelected"
      emit-value
      map-options
    >
      <template v-slot:no-option>
        <q-item>
          <q-item-section class="text-grey">No tours found</q-item-section>
        </q-item>
      </template>
    </q-select>

    <!-- Action Buttons -->
    <div v-if="showButtons" :class="buttonsClass">
      <q-btn
        flat
        round
        dense
        icon="refresh"
        :class="buttonSpacing"
        :loading="store.loading"
        @click="refreshData"
        title="Refresh tours data"
      />
      <q-btn
        flat
        round
        dense
        icon="share"
        :class="buttonSpacing"
        :disable="!store.selectedTour"
        @click="shareCurrentTour"
        title="Share current tour"
      />
      <q-btn
        flat
        round
        dense
        :icon="store.isAdmin ? 'admin_panel_settings' : 'lock'"
        :color="store.isAdmin ? 'positive' : 'grey'"
        :class="buttonSpacing"
        @click="toggleAdmin"
        :title="store.isAdmin ? 'Admin Mode (Click to logout)' : 'Admin Login'"
      />
    </div>

    <!-- Admin Actions -->
    <div v-if="showAdminActions && store.isAdmin" :class="adminActionsClass">
      <span class="text-body1 q-mr-sm">Tour actions:</span>
      <q-btn-group flat>
        <q-btn flat round dense icon="add" title="Create Tour" @click="openAddTourDialog" />
        <q-btn
          flat
          round
          dense
          icon="edit"
          title="Edit Tour"
          :disable="!store.selectedTour"
          @click="openEditTourDialog"
        />
        <q-btn
          flat
          round
          dense
          icon="delete"
          title="Delete Tour"
          :disable="!store.selectedTour"
          @click="confirmDeleteTour"
        />
      </q-btn-group>
      <q-separator v-if="!isMobile" vertical spaced class="q-mx-md" />
      <q-space v-if="isMobile" />
      <q-btn
        :class="isMobile ? '' : 'q-px-md'"
        color="primary"
        icon="add"
        label="Add Leg"
        dense
        :disable="!store.selectedTour"
        @click="openAddLegDialog"
      />
    </div>
  </div>
</template>

<script setup>
import { defineProps, inject } from 'vue'
defineProps({
  showLogoTitle: { type: Boolean, default: false },
  showSelect: { type: Boolean, default: false },
  showButtons: { type: Boolean, default: false },
  showAdminActions: { type: Boolean, default: false },
  titleClass: { type: String, default: '' },
  selectStyle: { type: String, default: 'min-width: 200px' },
  buttonsClass: { type: String, default: '' },
  buttonSpacing: { type: String, default: 'q-ml-sm' },
  adminActionsClass: { type: String, default: '' },
  isMobile: { type: Boolean, default: false },
})

// Inject parent methods and data
const {
  store,
  tourOptions,
  onTourSelected,
  refreshData,
  shareCurrentTour,
  toggleAdmin,
  openAddTourDialog,
  openEditTourDialog,
  confirmDeleteTour,
  openAddLegDialog,
} = inject('headerMethods')
</script>
