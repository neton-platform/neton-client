<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import { Card, List, ListItem, ListItemMeta, Button, Tag, message } from 'ant-design-vue';
import { requestClient } from '#/api/request';

interface SocialUser {
  id: number;
  type: number;
  typeName: string;
  openid: string;
  nickname: string;
  avatar: string;
}

const loading = ref(false);
const socialUsers = ref<SocialUser[]>([]);

async function loadSocialUsers() {
  loading.value = true;
  try {
    const result = await requestClient.get('/member/social-user/get');
    socialUsers.value = result ? [result] : [];
  } catch {
    socialUsers.value = [];
  } finally {
    loading.value = false;
  }
}

async function handleUnbind(type: number, openid: string) {
  try {
    await requestClient.delete('/member/social-user/unbind', {
      data: { type, openid },
    });
    message.success('Unbound successfully');
    loadSocialUsers();
  } catch {
    message.error('Failed to unbind');
  }
}

onMounted(() => {
  loadSocialUsers();
});
</script>

<template>
  <div class="p-4">
    <Card title="Social Accounts" :loading="loading">
      <List :data-source="socialUsers" item-layout="horizontal">
        <template #renderItem="{ item }">
          <ListItem>
            <template #actions>
              <Button type="link" danger @click="handleUnbind(item.type, item.openid)">
                Unbind
              </Button>
            </template>
            <ListItemMeta :title="item.nickname || item.typeName" :description="item.openid">
              <template #avatar>
                <Tag color="blue">{{ item.typeName }}</Tag>
              </template>
            </ListItemMeta>
          </ListItem>
        </template>
      </List>
      <div v-if="socialUsers.length === 0 && !loading" class="py-8 text-center text-gray-400">
        No social accounts bound
      </div>
    </Card>
  </div>
</template>
