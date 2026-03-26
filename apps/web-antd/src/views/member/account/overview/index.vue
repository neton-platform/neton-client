<script lang="ts" setup>
import type { AccountSummary } from '#/api/member/account-center';

import { onMounted, ref, computed } from 'vue';

import { Card, Col, Row, Space, Statistic, message, Button } from 'ant-design-vue';
import { useRouter } from 'vue-router';
import dayjs from 'dayjs';

import { fetchAccountSummary } from '#/api/member/account-center';

const router = useRouter();
const loading = ref(false);
const summary = ref<AccountSummary>();

const summaryCards = computed(() => {
  const data = summary.value;
  return [
    {
      title: '账户余额 (元)',
      value: data?.balance ?? '--',
    },
    {
      title: '累计消费 (元)',
      value: data?.totalCharged ?? '--',
    },
    {
      title: '今日调用次数',
      value: data?.todayCalls ?? 0,
    },
    {
      title: '今日扣费 (元)',
      value: data?.todayChargeAmount ?? '--',
    },
  ];
});

function formatTime(value?: null | number | string) {
  if (value === undefined || value === null || value === '') return '--';

  if (typeof value === 'number') {
    const timestamp = value < 1e12 ? value * 1000 : value;
    return dayjs(timestamp).format('YYYY-MM-DD HH:mm:ss');
  }

  const normalized = value.trim();
  if (!normalized) return '--';

  if (/^\d+$/.test(normalized)) {
    const numeric = Number(normalized);
    const timestamp = numeric < 1e12 ? numeric * 1000 : numeric;
    return dayjs(timestamp).format('YYYY-MM-DD HH:mm:ss');
  }

  return dayjs(normalized).format('YYYY-MM-DD HH:mm:ss');
}

async function loadSummary() {
  loading.value = true;
  try {
    summary.value = await fetchAccountSummary();
  } catch (error) {
    console.error(error);
    message.error('加载账户概览失败');
  } finally {
    loading.value = false;
  }
}

function goto(path: string) {
  router.push(path);
}

onMounted(() => {
  loadSummary();
});
</script>

<template>
  <div class="p-4">
    <Space direction="vertical" size="large" class="w-full">
      <Row :gutter="16">
        <Col v-for="card in summaryCards" :key="card.title" :xs="24" :sm="12" :md="6">
          <Card :loading="loading" size="small" class="shadow-sm">
            <Statistic :title="card.title" :value="card.value" />
          </Card>
        </Col>
      </Row>

      <Card title="最近调用" :loading="loading">
        <template #extra>
          <Space>
            <Button type="link" @click="goto('/member/account/logs')">查看请求日志</Button>
            <Button type="link" @click="goto('/member/account/charges')">查看消费记录</Button>
          </Space>
        </template>
        <div v-if="summary?.lastCall" class="grid gap-3 md:grid-cols-2">
          <div class="rounded border border-gray-100 p-3 text-sm leading-6">
            <div>Trace ID：{{ summary.lastCall.traceId }}</div>
            <div>API：{{ summary.lastCall.apiName || summary.lastCall.apiCode }}</div>
            <div>调用时间：{{ formatTime(summary.lastCall.requestTime) }}</div>
          </div>
          <div class="rounded border border-gray-100 p-3 text-sm leading-6">
            <div>HTTP 状态：{{ summary.lastCall.httpStatus }}</div>
            <div>业务状态：{{ summary.lastCall.responseStatus }}</div>
            <div>扣费金额：{{ summary.lastCall.deductAmount ?? '--' }} 元</div>
          </div>
        </div>
        <div v-else class="text-gray-500">暂无调用记录</div>
      </Card>
    </Space>
  </div>
</template>
