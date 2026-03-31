<script lang="ts" setup>
import type { RangeValue } from '../components/types';
import type { LogDetail, LogRecord } from '#/api/member/account-center';

import { onMounted, reactive, ref } from 'vue';

import {
  Button,
  Card,
  Descriptions,
  DescriptionsItem,
  Drawer,
  Input,
  Select,
  Space,
  Spin,
  Table,
  Tag,
  message,
} from 'ant-design-vue';
import dayjs from 'dayjs';

import RangeTabs from '../components/range-tabs.vue';
import { fetchLogDetail, fetchLogs } from '#/api/member/account-center';

const rangeValue = ref<RangeValue>({ range: '7d' });
const tableData = ref<LogRecord[]>([]);
const loading = ref(false);
const pagination = reactive({ current: 1, pageSize: 20, total: 0 });
const filters = reactive({ apiCode: '', status: undefined as undefined | 'SUCCESS' | 'FAIL' });

const detailVisible = ref(false);
const detailLoading = ref(false);
const detailData = ref<LogDetail>();
const detailTraceId = ref<string>('');

const columns = [
  { title: '调用时间', dataIndex: 'requestTime', key: 'requestTime' },
  { title: 'API', dataIndex: 'apiName', key: 'apiName' },
  { title: 'HTTP', dataIndex: 'httpStatus', key: 'httpStatus' },
  { title: '业务状态', dataIndex: 'responseStatus', key: 'responseStatus' },
  { title: '耗时 (ms)', dataIndex: 'responseTimeMs', key: 'responseTimeMs' },
  { title: '扣费 (元)', dataIndex: 'deductAmount', key: 'deductAmount' },
  { title: 'Trace ID', dataIndex: 'traceId', key: 'traceId' },
  { title: '操作', key: 'action' },
];

function formatDate(value?: string) {
  if (!value) return '--';
  return dayjs(value).format('YYYY-MM-DD HH:mm:ss');
}

function buildParams(page?: number) {
  const params: Record<string, any> = {
    range: rangeValue.value.range,
    pageNo: page ?? pagination.current,
    pageSize: pagination.pageSize,
  };
  if (filters.apiCode) {
    params.apiCode = filters.apiCode;
  }
  if (filters.status) {
    params.status = filters.status;
  }
  if (rangeValue.value.range === 'custom') {
    params.startTime = rangeValue.value.startTime;
    params.endTime = rangeValue.value.endTime;
  }
  return params;
}

async function loadLogs(page?: number) {
  if (page) {
    pagination.current = page;
  }
  loading.value = true;
  try {
    const result = await fetchLogs(buildParams());
    tableData.value = result.list || [];
    pagination.total = result.total || 0;
  } catch (error) {
    console.error(error);
    message.error('加载请求日志失败');
  } finally {
    loading.value = false;
  }
}

function handleRangeChange(value: RangeValue) {
  rangeValue.value = value;
  loadLogs(1);
}

function handleSearch() {
  loadLogs(1);
}

function handleTableChange(pag: any) {
  pagination.current = pag.current ?? 1;
  pagination.pageSize = pag.pageSize ?? 20;
  loadLogs();
}

function successStatus(record: LogRecord) {
  const http2xx = record.httpStatus >= 200 && record.httpStatus < 300;
  return http2xx && record.responseStatus === 'SUCCESS';
}

async function handleViewDetail(record: LogRecord) {
  detailTraceId.value = record.traceId;
  detailVisible.value = true;
  detailLoading.value = true;
  try {
    detailData.value = await fetchLogDetail(record.traceId);
  } catch (error) {
    console.error(error);
    message.error('加载日志详情失败');
  } finally {
    detailLoading.value = false;
  }
}

function closeDetail() {
  detailVisible.value = false;
  detailData.value = undefined;
}

function pretty(content?: string) {
  if (!content) return '--';
  try {
    return JSON.stringify(JSON.parse(content), null, 2);
  } catch (error) {
    return content;
  }
}

const statusOptions = [
  { label: '全部', value: undefined },
  { label: '成功', value: 'SUCCESS' },
  { label: '失败', value: 'FAIL' },
];

onMounted(() => {
  loadLogs();
});
</script>

<template>
  <div class="p-4">
    <Card title="请求日志">
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
        <Select
          v-model:value="filters.status"
          placeholder="状态"
          style="width: 160px"
          :options="statusOptions"
          allow-clear
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
          <template v-if="column.key === 'requestTime'">
            {{ formatDate(record.requestTime) }}
          </template>
          <template v-else-if="column.key === 'apiName'">
            {{ record.apiName || record.apiCode }}
          </template>
          <template v-else-if="column.key === 'responseStatus'">
            <Tag :color="successStatus(record) ? 'green' : 'red'">
              {{ record.responseStatus }}
            </Tag>
          </template>
          <template v-else-if="column.key === 'action'">
            <Button type="link" size="small" @click="handleViewDetail(record)">详情</Button>
          </template>
          <template v-else>
            {{ record[column.dataIndex] ?? '--' }}
          </template>
        </template>
      </Table>
    </Card>

    <Drawer
      title="日志详情"
      :open="detailVisible"
      width="720"
      destroy-on-close
      @close="closeDetail"
    >
      <Spin :spinning="detailLoading">
        <template v-if="detailData">
          <Descriptions bordered size="small" :column="1">
            <DescriptionsItem label="Trace ID">{{ detailTraceId }}</DescriptionsItem>
            <DescriptionsItem label="API">{{ detailData.apiName || detailData.apiCode }}</DescriptionsItem>
            <DescriptionsItem label="请求时间">{{ formatDate(detailData.requestTime) }}</DescriptionsItem>
            <DescriptionsItem label="HTTP 状态">{{ detailData.httpStatus }}</DescriptionsItem>
            <DescriptionsItem label="业务状态">{{ detailData.responseStatus }}</DescriptionsItem>
            <DescriptionsItem label="耗时 (ms)">{{ detailData.responseTimeMs }}</DescriptionsItem>
            <DescriptionsItem label="扣费 (元)">{{ detailData.deductAmount ?? '--' }}</DescriptionsItem>
            <DescriptionsItem label="请求 IP">{{ detailData.requestIp ?? '--' }}</DescriptionsItem>
          </Descriptions>

          <Card title="请求头" size="small" class="mt-3">
            <div v-if="detailData.requestHeaders?.length">
              <div v-for="header in detailData.requestHeaders" :key="header.name">
                <strong>{{ header.name }}:</strong> {{ header.value }}
              </div>
            </div>
            <div v-else class="text-gray-500">无</div>
          </Card>

          <Card title="请求参数" size="small" class="mt-3">
            <pre>{{ pretty(detailData.requestParams) }}</pre>
          </Card>

          <Card title="请求体" size="small" class="mt-3">
            <pre>{{ pretty(detailData.requestBody) }}</pre>
          </Card>

          <Card title="响应体" size="small" class="mt-3">
            <pre>{{ pretty(detailData.responseBody) }}</pre>
          </Card>

          <Card v-if="detailData.chargeRecord" title="关联扣费" size="small" class="mt-3">
            <div>时间：{{ formatDate(detailData.chargeRecord?.chargeTime as string) }}</div>
            <div>金额：{{ detailData.chargeRecord?.price ?? '--' }} 元</div>
            <div>状态：{{ detailData.chargeRecord?.chargeStatus === 1 ? '成功' : '失败' }}</div>
          </Card>
        </template>
        <template v-else>
          <div class="text-gray-500">暂无数据</div>
        </template>
      </Spin>
    </Drawer>
  </div>
</template>
