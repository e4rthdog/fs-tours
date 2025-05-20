<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated class="bg-dark text-white">
      <q-toolbar>
        <q-toolbar-title>
          <q-avatar size="32px" class="q-mr-sm">
            <img src="https://i.imgur.com/4M34hi2.png" alt="Logo" />
          </q-avatar>
          FS Tours
        </q-toolbar-title>
        <!-- Add header actions here if needed -->
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>

    <q-footer elevated class="bg-dark text-white">
      <div class="q-pa-sm q-gutter-sm row items-center justify-between">
        <div>FS Tours &copy; {{ new Date().getFullYear() }}</div>
        <div class="text-caption">Map last updated: --:--:--Z</div>
      </div>
    </q-footer>
  </q-layout>
</template>

<script setup>
// No logic needed for static layout
</script>

<style scoped>

</style>
