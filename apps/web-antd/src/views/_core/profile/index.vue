<script setup lang="ts">
import { onMounted, ref } from 'vue';

import { Page } from '@vben/common-ui';

import { Card, Descriptions, DescriptionsItem, Avatar } from 'ant-design-vue';

import { getUserInfoApi } from '#/api';

const userInfo = ref<any>(null);
const loading = ref(false);

async function loadProfile() {
  loading.value = true;
  try {
    userInfo.value = await getUserInfoApi();
  } finally {
    loading.value = false;
  }
}

onMounted(loadProfile);
</script>

<template>
  <Page auto-content-height>
    <Card title="Profile" :loading="loading">
      <template v-if="userInfo">
        <div class="mb-6 flex items-center gap-4">
          <Avatar :src="userInfo.avatar" :size="80">
            {{ userInfo.nickname?.charAt(0) }}
          </Avatar>
          <div>
            <h2 class="text-xl font-medium">{{ userInfo.nickname }}</h2>
            <p class="text-gray-500">{{ userInfo.mobile }}</p>
          </div>
        </div>
        <Descriptions bordered :column="2">
          <DescriptionsItem label="Nickname">{{ userInfo.nickname }}</DescriptionsItem>
          <DescriptionsItem label="Phone">{{ userInfo.mobile }}</DescriptionsItem>
          <DescriptionsItem label="Level">{{ userInfo.levelName || '-' }}</DescriptionsItem>
          <DescriptionsItem label="Points">{{ userInfo.point ?? 0 }}</DescriptionsItem>
          <DescriptionsItem label="Experience">{{ userInfo.experience ?? 0 }}</DescriptionsItem>
        </Descriptions>
      </template>
    </Card>
  </Page>
</template>
