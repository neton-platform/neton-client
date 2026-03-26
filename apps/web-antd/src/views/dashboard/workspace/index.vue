<script lang="ts" setup>
import type { AccountSummary, ChargeRecord, LogRecord } from '#/api/member/account-center';

import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

import { Button, Card, Col, Empty, Row, Space, Spin, Statistic, Table, Tag, message } from 'ant-design-vue';
import dayjs from 'dayjs';

import {
  fetchAccountSummary,
  fetchRecentCharges,
  fetchRecentLogs,
} from '#/api/member/account-center';

const router = useRouter();
const summaryLoading = ref(false);
const chargesLoading = ref(false);
const logsLoading = ref(false);
const refreshLoading = ref(false);

const summary = ref<AccountSummary>();
const charges = ref<ChargeRecord[]>([]);
const logs = ref<LogRecord[]>([]);

const chargesError = ref<string | null>(null);
const logsError = ref<string | null>(null);

const summaryCards = computed(() => {
  const data = summary.value;
  return [
    { title: '账户余额 (元)', value: data?.balance ?? '--' },
    { title: '累计消费 (元)', value: data?.totalCharged ?? '--' },
    { title: '今日调用次数', value: data?.todayCalls ?? '--' },
    { title: '今日扣费 (元)', value: data?.todayChargeAmount ?? '--' },
  ];
});

function formatTime(value?: string) {
  if (!value) return '--';
  return dayjs(value).format('YYYY-MM-DD HH:mm:ss');
}

function formatStatus(status: number | string) {
  if (typeof status === 'number') {
    return status === 1 ? '成功' : '失败';
  }
  return status;
}

function formatAmount(value?: string | number) {
  if (value === undefined || value === null || value === '') return '--';
  return typeof value === 'number' ? value.toFixed(2) : value;
}

async function loadSummary() {
  summaryLoading.value = true;
  try {
    summary.value = await fetchAccountSummary();
  } catch (error) {
    console.error(error);
    message.error('加载账户摘要失败');
  } finally {
    summaryLoading.value = false;
  }
}

async function loadCharges() {
  chargesLoading.value = true;
  chargesError.value = null;
  try {
    const result = await fetchRecentCharges();
    charges.value = result.list || [];
  } catch (error) {
    console.error(error);
    chargesError.value = '加载消费记录失败';
    message.error('加载消费记录失败');
  } finally {
    chargesLoading.value = false;
  }
}

async function loadLogs() {
  logsLoading.value = true;
  logsError.value = null;
  try {
    const result = await fetchRecentLogs();
    logs.value = result.list || [];
  } catch (error) {
    console.error(error);
    logsError.value = '加载请求日志失败';
    message.error('加载请求日志失败');
  } finally {
    logsLoading.value = false;
  }
}

async function loadAll() {
  refreshLoading.value = true;
  try {
    await Promise.all([loadSummary(), loadCharges(), loadLogs()]);
  } finally {
    refreshLoading.value = false;
  }
}

function goto(path: string, extra?: Record<string, any>) {
  router.push({ path, query: extra }).catch((error) => {
    console.error('Navigation failed:', error);
  });
}

function viewChargeList() {
  goto('/member/account/charges');
}

function viewLogList() {
  goto('/member/account/logs');
}

function viewLogDetail(item: LogRecord) {
  goto('/member/account/logs', { traceId: item.traceId });
}

const chargeColumns = [
  { title: '扣费时间', dataIndex: 'chargeTime', key: 'chargeTime' },
  { title: 'API', dataIndex: 'apiName', key: 'apiName' },
  { title: '金额 (元)', dataIndex: 'price', key: 'price' },
  { title: '余额变动', dataIndex: 'balanceAfter', key: 'balanceChange' },
  { title: '状态', dataIndex: 'chargeStatus', key: 'chargeStatus' },
  { title: 'Trace ID', dataIndex: 'traceId', key: 'traceId' },
];

const logColumns = [
  { title: '调用时间', dataIndex: 'requestTime', key: 'requestTime' },
  { title: 'API', dataIndex: 'apiName', key: 'apiName', ellipsis: true },
  { title: '业务状态', dataIndex: 'responseStatus', key: 'responseStatus' },
  { title: '扣费 (元)', dataIndex: 'deductAmount', key: 'deductAmount' },
  { title: '操作', key: 'action' },
];

onMounted(() => {
  loadAll();
});
</script>

<template>
  <div class="p-5 space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-lg font-medium">账户工作台</div>
        <div class="text-gray-500">快速掌握余额与最新消费/日志</div>
      </div>
      <Button type="primary" :loading="refreshLoading" @click="loadAll">刷新</Button>
    </div>

    <Row :gutter="16">
      <Col v-for="card in summaryCards" :key="card.title" :xs="24" :sm="12" :md="6">
        <Card size="small" :loading="summaryLoading">
          <Statistic :title="card.title" :value="card.value" />
        </Card>
      </Col>
    </Row>

    <Card title="最近一次调用" :loading="summaryLoading" class="last-call-card">
      <template v-if="summary?.lastCall">
        <div class="last-call-grid">
          <div class="last-call-main">
            <div class="text-xs uppercase text-gray-500">Trace ID</div>
            <div class="flex items-center gap-2">
              <span class="font-mono text-sm">{{ summary.lastCall.traceId }}</span>
              <Button type="link" size="small" @click="goto('/member/account/logs', { traceId: summary.lastCall.traceId })">查看详情</Button>
            </div>
            <div class="text-lg font-medium">{{ summary.lastCall.apiName || summary.lastCall.apiCode }}</div>
            <div class="text-sm text-gray-500">{{ formatTime(summary.lastCall.requestTime) }}</div>
          </div>
          <div class="last-call-meta">
            <div>
              <div class="label">HTTP 状态</div>
              <div class="value">{{ summary.lastCall.httpStatus }}</div>
            </div>
            <div>
              <div class="label">业务状态</div>
              <Tag :color="summary.lastCall.responseStatus === 'SUCCESS' ? 'green' : 'red'">
                {{ summary.lastCall.responseStatus }}
              </Tag>
            </div>
            <div>
              <div class="label">扣费金额</div>
              <div class="value">{{ formatAmount(summary.lastCall.deductAmount) }} 元</div>
            </div>
          </div>
        </div>
      </template>
      <template v-else>
        <Empty description="暂无调用记录" />
      </template>
    </Card>

    <div class="stacked-cards">
      <Card title="近期消费记录">
        <template #extra>
          <Space>
            <Button type="link" size="small" @click="viewChargeList">查看更多</Button>
          </Space>
        </template>
          <Spin :spinning="chargesLoading">
            <template v-if="chargesError">
              <div class="text-center text-red-500">
                {{ chargesError }}
                <Button type="link" size="small" @click="loadCharges">重试</Button>
              </div>
            </template>
            <template v-else>
              <Table
                :columns="chargeColumns"
                :data-source="charges"
                :pagination="false"
                size="small"
                row-key="traceId"
                class="overview-table"
                :scroll="{ x: 860 }"
              >
                <template #bodyCell="{ column, record }">
                  <template v-if="column.key === 'chargeTime'">
                    {{ formatTime(record.chargeTime) }}
                  </template>
                  <template v-else-if="column.key === 'apiName'">
                    {{ record.apiName || record.apiCode }}
                  </template>
                  <template v-else-if="column.key === 'balanceChange'">
                    {{ formatAmount(record.balanceBefore) }} → {{ formatAmount(record.balanceAfter) }}
                  </template>
                  <template v-else-if="column.key === 'chargeStatus'">
                    <Tag :color="record.chargeStatus === 1 ? 'green' : 'red'">
                      {{ formatStatus(record.chargeStatus) }}
                    </Tag>
                  </template>
                  <template v-else>
                    {{ record[column.dataIndex] ?? '--' }}
                  </template>
                </template>
              </Table>
              <Empty v-if="!chargesLoading && !charges.length" description="暂无扣费记录" />
            </template>
          </Spin>
      </Card>

      <Card title="近期请求日志">
        <template #extra>
          <Space>
            <Button type="link" size="small" @click="viewLogList">查看更多</Button>
          </Space>
        </template>
          <Spin :spinning="logsLoading">
            <template v-if="logsError">
              <div class="text-center text-red-500">
                {{ logsError }}
                <Button type="link" size="small" @click="loadLogs">重试</Button>
              </div>
            </template>
            <template v-else>
              <Table
                :columns="logColumns"
                :data-source="logs"
                :pagination="false"
                size="small"
                row-key="traceId"
                class="overview-table"
                :scroll="{ x: 760 }"
              >
                <template #bodyCell="{ column, record }">
                  <template v-if="column.key === 'requestTime'">
                    {{ formatTime(record.requestTime) }}
                  </template>
                  <template v-else-if="column.key === 'apiName'">
                    {{ record.apiName || record.apiCode }}
                  </template>
                  <template v-else-if="column.key === 'responseStatus'">
                    <Tag :color="record.responseStatus === 'SUCCESS' ? 'green' : 'red'">
                      {{ record.responseStatus }}
                    </Tag>
                  </template>
                  <template v-else-if="column.key === 'deductAmount'">
                    {{ formatAmount(record.deductAmount) }}
                  </template>
                  <template v-else-if="column.key === 'action'">
                    <Button type="link" size="small" @click="viewLogDetail(record)">详情</Button>
                  </template>
                  <template v-else>
                    {{ record[column.dataIndex] ?? '--' }}
                  </template>
                </template>
              </Table>
              <Empty v-if="!logsLoading && !logs.length" description="暂无请求日志" />
            </template>
          </Spin>
      </Card>
    </div>
  </div>
</template>

<style scoped>
.last-call-card {
  border-left: 4px solid #1677ff;
}

.last-call-grid {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

.last-call-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 1rem;
}

.last-call-meta .label {
  font-size: 12px;
  color: #888;
  text-transform: uppercase;
}

.last-call-meta .value {
  font-size: 16px;
  font-weight: 600;
}

.stacked-cards {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.overview-table :deep(.ant-table-thead > tr > th) {
  white-space: nowrap;
}
</style>
