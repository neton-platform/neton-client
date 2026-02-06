<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import { Card, Descriptions, DescriptionsItem, Avatar, Button, message } from 'ant-design-vue';
import { requestClient } from '#/api/request';

interface MemberUser {
  id: number;
  nickname: string;
  avatar: string;
  mobile: string;
  levelName?: string;
  experience?: number;
  point?: number;
}

const userInfo = ref<MemberUser | null>(null);
const loading = ref(false);

async function loadUserInfo() {
  loading.value = true;
  try {
    userInfo.value = await requestClient.get<MemberUser>('/member/user/get');
  } catch {
    message.error('Failed to load user info');
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadUserInfo();
});
</script>

<template>
  <div class="p-4">
    <Card title="Basic Information" :loading="loading">
      <template v-if="userInfo">
        <div class="mb-4 flex items-center gap-4">
          <Avatar :src="userInfo.avatar" :size="64">
            {{ userInfo.nickname?.charAt(0) }}
          </Avatar>
          <div>
            <h3 class="text-lg font-medium">{{ userInfo.nickname }}</h3>
            <p class="text-gray-500">{{ userInfo.mobile }}</p>
          </div>
        </div>
        <Descriptions bordered :column="2">
          <DescriptionsItem label="User ID">{{ userInfo.id }}</DescriptionsItem>
          <DescriptionsItem label="Nickname">{{ userInfo.nickname }}</DescriptionsItem>
          <DescriptionsItem label="Phone">{{ userInfo.mobile }}</DescriptionsItem>
          <DescriptionsItem label="Level">{{ userInfo.levelName || '-' }}</DescriptionsItem>
          <DescriptionsItem label="Points">{{ userInfo.point ?? 0 }}</DescriptionsItem>
          <DescriptionsItem label="Experience">{{ userInfo.experience ?? 0 }}</DescriptionsItem>
        </Descriptions>
      </template>
    </Card>
  </div>
</template>
