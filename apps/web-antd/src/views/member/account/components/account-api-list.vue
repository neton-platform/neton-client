<script lang="ts" setup>
import type { AccountApiItem } from '#/api/member/account-center';

import { Button, Empty, Result, Table, Tag } from 'ant-design-vue';

defineProps<{ 
  data?: AccountApiItem[];
  loading?: boolean;
  error?: Error | null;
}>();

const emit = defineEmits<{ (e: 'retry'): void; (e: 'view', api: AccountApiItem): void }>();

const columns = [
  { title: '请求方式', dataIndex: 'method', key: 'method' },
  { title: '路径', dataIndex: 'path', key: 'path' },
  { title: '接口名称', dataIndex: 'apiName', key: 'apiName' },
  { title: '状态', dataIndex: 'status', key: 'status' },
  { title: '操作', key: 'action' },
];

function statusColor(status?: string) {
  if (status === 'ENABLED') return 'success';
  return 'default';
}

function handleRetry() {
  emit('retry');
}

function handleView(record: AccountApiItem) {
  emit('view', record);
}
</script>

<template>
  <Result
    v-if="!loading && error"
    status="warning"
    title="接口列表加载失败"
    sub-title="请稍后重试"
  >
    <template #extra>
      <Button type="primary" @click="handleRetry">重新加载</Button>
    </template>
  </Result>
  <div v-else>
    <Empty v-if="!loading && (!data || data.length === 0)" description="暂无接口" />
    <Table
      v-else
      :columns="columns"
      :data-source="data"
      size="small"
      :loading="loading"
      row-key="apiCode"
      :pagination="false"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'method'">
          <Tag color="blue">{{ record.method }}</Tag>
        </template>
        <template v-else-if="column.key === 'apiName'">
          <div>
            <div class="font-medium">{{ record.apiName || record.apiCode }}</div>
            <div class="text-xs text-gray-500">{{ record.description }}</div>
          </div>
        </template>
        <template v-else-if="column.key === 'status'">
          <Tag :color="statusColor(record.status)"> {{ record.status }} </Tag>
        </template>
        <template v-else-if="column.key === 'action'">
          <Button type="link" size="small" @click="handleView(record)">查看文档</Button>
        </template>
      </template>
    </Table>
  </div>
</template>
