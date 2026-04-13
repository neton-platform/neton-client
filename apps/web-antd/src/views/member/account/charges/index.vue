<script lang="ts" setup>
import type { RangeValue } from '../components/types';
import type { ChargeRecord } from '#/api/member/account-center';

import { reactive, ref, onMounted } from 'vue';

import { Card, Input, Space, Table, Tag, message, Button } from 'ant-design-vue';
import dayjs from 'dayjs';

import RangeTabs from '../components/range-tabs.vue';
import { fetchCharges } from '#/api/member/account-center';

const rangeValue = ref<RangeValue>({ range: '7d' });
const tableData = ref<ChargeRecord[]>([]);
const loading = ref(false);
const pagination = reactive({ current: 1, pageSize: 20, total: 0 });
const filters = reactive({ apiCode: '' });

const columns = [
  { title: '时间', dataIndex: 'chargeTime', key: 'chargeTime' },
  { title: 'API', dataIndex: 'apiName', key: 'apiName' },
  { title: '类型', dataIndex: 'changeDirection', key: 'changeDirection' },
  { title: '金额 (元)', dataIndex: 'price', key: 'price' },
  { title: '余额变化 (元)', dataIndex: 'balanceDiff', key: 'balanceDiff' },
  { title: '状态', dataIndex: 'chargeStatus', key: 'chargeStatus' },
  { title: '备注', dataIndex: 'remark', key: 'remark' },
  { title: 'Trace ID', dataIndex: 'traceId', key: 'traceId' },
];

function formatDate(value?: string) {
  if (!value) return '--';
  return dayjs(value).format('YYYY-MM-DD HH:mm:ss');
}

function formatStatus(status: number) {
  return status === 1 ? '成功' : '失败';
}

function parseAmount(value?: string) {
  if (!value) return 0;
  const num = Number(value);
  return Number.isNaN(num) ? 0 : num;
}

function getChangeDirection(record: ChargeRecord) {
  const before = parseAmount(record.balanceBefore);
  const after = parseAmount(record.balanceAfter);
  if (after > before) return { text: '充值', color: 'green' };
  if (after < before) return { text: '扣减', color: 'red' };
  return { text: '无变化', color: 'default' };
}

function buildParams(page?: number) {
  const params: Record<string, any> = {
    type: 'DEDUCT',
    range: rangeValue.value.range,
    pageNo: page ?? pagination.current,
    pageSize: pagination.pageSize,
  };
  if (filters.apiCode) {
    params.apiCode = filters.apiCode;
  }
  if (rangeValue.value.range === 'custom') {
    params.startTime = rangeValue.value.startTime;
    params.endTime = rangeValue.value.endTime;
  }
  return params;
}

async function loadCharges(page?: number) {
  if (page) {
    pagination.current = page;
  }
  loading.value = true;
  try {
    const result = await fetchCharges(buildParams());
    tableData.value = result.list || [];
    pagination.total = result.total || 0;
  } catch (error) {
    console.error(error);
    message.error('加载消费记录失败');
  } finally {
    loading.value = false;
  }
}

function handleRangeChange(value: RangeValue) {
  rangeValue.value = value;
  loadCharges(1);
}

function handleSearch() {
  loadCharges(1);
}

function handleTableChange(pag: any) {
  pagination.current = pag.current ?? 1;
  pagination.pageSize = pag.pageSize ?? 20;
  loadCharges();
}

onMounted(() => {
  loadCharges();
});
</script>

<template>
  <div class="p-4">
    <Card title="消费记录">
      <template #extra>
        <RangeTabs :value="rangeValue" @change="handleRangeChange" />
      </template>
      <Space class="mb-3" :size="12" >
        <Input
          v-model:value="filters.apiCode"
          placeholder="API 编码"
          allow-clear
          style="width: 200px"
        />
        <Button type="primary" @click="handleSearch">查询</Button>
      </Space>
      <Table
        :columns="columns"
        :data-source="tableData"
        :loading="loading"
        :pagination="{
          current: pagination.current,
          pageSize: pagination.pageSize,
          total: pagination.total,
          showTotal: (total) => `共 ${total} 条`,
        }"
        row-key="traceId"
        @change="handleTableChange"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'chargeTime'">
            {{ formatDate(record.chargeTime) }}
          </template>
          <template v-else-if="column.key === 'apiName'">
            {{ record.apiName || record.apiCode }}
          </template>
          <template v-else-if="column.key === 'changeDirection'">
            <Tag :color="getChangeDirection(record).color">
              {{ getChangeDirection(record).text }}
            </Tag>
          </template>
          <template v-else-if="column.key === 'balanceDiff'">
            {{ record.balanceBefore }} → {{ record.balanceAfter }}
          </template>
          <template v-else-if="column.key === 'chargeStatus'">
            <Tag :color="record.chargeStatus === 1 ? 'green' : 'red'">
              {{ formatStatus(record.chargeStatus) }}
            </Tag>
          </template>
          <template v-else-if="column.key === 'remark'">
            <span class="whitespace-pre-wrap break-all">
              {{ record.remark || '--' }}
            </span>
          </template>
          <template v-else>
            {{ record[column.dataIndex] ?? '--' }}
          </template>
        </template>
      </Table>
    </Card>
  </div>
</template>
