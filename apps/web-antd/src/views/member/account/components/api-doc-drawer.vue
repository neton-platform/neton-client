<script lang="ts" setup>
import type { AccountApiItem, ApiSchemaField } from '#/api/member/account-center';

import { Drawer, Descriptions, Table, Typography } from 'ant-design-vue';

const props = defineProps<{ 
  api?: AccountApiItem | null;
  open: boolean;
}>();

const emit = defineEmits<{ (e: 'close'): void }>();

const schemaColumns = [
  { title: '字段', dataIndex: 'field', key: 'field' },
  { title: '类型', dataIndex: 'type', key: 'type' },
  { title: '必填', dataIndex: 'required', key: 'required' },
  { title: '说明', dataIndex: 'description', key: 'description' },
];

function renderRequired(field?: boolean) {
  return field ? '是' : '否';
}

function handleClose() {
  emit('close');
}
</script>

<template>
  <Drawer :open="open" :width="720" title="接口文档" destroy-on-close @close="handleClose">
    <template v-if="api">
      <Descriptions :column="1" size="small" bordered>
        <Descriptions.Item label="接口名称">{{ api.apiName || api.apiCode }}</Descriptions.Item>
        <Descriptions.Item label="请求方式">{{ api.method }}</Descriptions.Item>
        <Descriptions.Item label="请求路径">{{ api.path }}</Descriptions.Item>
        <Descriptions.Item label="限流策略">{{ api.rateLimit || '默认' }}</Descriptions.Item>
      </Descriptions>

      <section class="mt-4">
        <h4 class="text-base font-medium">请求参数</h4>
        <Table
          size="small"
          :columns="schemaColumns"
          :data-source="api.requestSchema as ApiSchemaField[]"
          row-key="field"
          :pagination="false"
        >
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'required'">
              {{ renderRequired(record.required) }}
            </template>
          </template>
        </Table>
        <Typography.Paragraph code class="mt-2 pre-block">{{ api.requestExample || '{}' }}</Typography.Paragraph>
      </section>

      <section class="mt-4">
        <h4 class="text-base font-medium">响应结果</h4>
        <Table
          size="small"
          :columns="schemaColumns"
          :data-source="api.responseSchema as ApiSchemaField[]"
          row-key="field"
          :pagination="false"
        >
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'required'">
              {{ renderRequired(record.required) }}
            </template>
          </template>
        </Table>
        <Typography.Paragraph code class="mt-2 pre-block">{{ api.responseExample || '{}' }}</Typography.Paragraph>
      </section>
    </template>
  </Drawer>
</template>

<style scoped>
.pre-block {
  white-space: pre-wrap;
  background-color: #f6f8fa;
  padding: 12px;
  border-radius: 6px;
  max-height: 280px;
  overflow: auto;
}

section + section {
  margin-top: 24px;
}
</style>
