<script lang="ts" setup>
import type { AccountCredential } from '#/api/member/account-center';

import { computed, onBeforeUnmount, ref, watch } from 'vue';

import { Alert, Button, Card, Descriptions, Result, Space, Typography, message } from 'ant-design-vue';

const props = defineProps<{ 
  credential?: AccountCredential;
  loading?: boolean;
  error?: Error | null;
}>();

const emit = defineEmits<{ (e: 'retry'): void }>();

const revealSecret = ref(false);
const timerId = ref<number>();

function clearTimer() {
  if (timerId.value) {
    window.clearTimeout(timerId.value);
    timerId.value = undefined;
  }
}

watch(revealSecret, (visible) => {
  clearTimer();
  if (visible) {
    timerId.value = window.setTimeout(() => {
      revealSecret.value = false;
    }, 30_000);
  }
});

onBeforeUnmount(() => {
  clearTimer();
});

const clientIdText = computed(() => {
  if (!props.credential) return '--';
  return revealSecret.value ? props.credential.clientId : props.credential.clientIdMasked;
});

const clientSecretText = computed(() => {
  if (!props.credential) return '--';
  return revealSecret.value ? props.credential.clientSecret : props.credential.clientSecretMasked;
});

const toggleText = computed(() => (revealSecret.value ? '隐藏密钥' : '显示密钥'));

async function copy(text?: string, label?: string) {
  if (!text) return;
  try {
    await navigator.clipboard.writeText(text);
    message.success(`${label ?? '内容'}已复制`);
  } catch (error) {
    console.error(error);
    message.error('复制失败，请检查浏览器权限');
  }
}

function handleRetry() {
  emit('retry');
}

function handleToggle() {
  if (!props.credential) return;
  revealSecret.value = !revealSecret.value;
}
</script>

<template>
  <Card title="账户凭证" :loading="loading" class="shadow-sm">
    <template #extra>
      <Button size="small" type="link" :disabled="!credential" @click="handleToggle">
        {{ toggleText }}
      </Button>
    </template>

    <Result
      v-if="!loading && error"
      status="warning"
      title="加载凭证失败"
      sub-title="请稍后重试"
    >
      <template #extra>
        <Button type="primary" @click="handleRetry">重新加载</Button>
      </template>
    </Result>

    <div v-else>
      <Descriptions :column="1" size="small" bordered>
        <Descriptions.Item label="Client ID">
          <Space size="small">
            <Typography.Text code>{{ clientIdText }}</Typography.Text>
            <Button size="small" type="link" @click="copy(credential?.clientId, 'Client ID')">复制</Button>
          </Space>
        </Descriptions.Item>
        <Descriptions.Item label="签名密钥">
          <Space size="small">
            <Typography.Text code>{{ clientSecretText }}</Typography.Text>
            <Button size="small" type="link" @click="copy(credential?.clientSecret, '签名密钥')">复制</Button>
          </Space>
        </Descriptions.Item>
      </Descriptions>

      <Alert
        class="mt-4"
        type="warning"
        show-icon
        message="请勿泄露密钥，显示 30 秒后自动隐藏"
      />
    </div>
  </Card>
</template>
