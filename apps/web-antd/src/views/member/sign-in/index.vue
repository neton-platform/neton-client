<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import { Card, Button, Table, Statistic, Row, Col, message } from 'ant-design-vue';
import { requestClient } from '#/api/request';

const loading = ref(false);
const summary = ref<any>(null);
const records = ref<any[]>([]);
const total = ref(0);
const pageNo = ref(1);

const columns = [
  { title: 'Day', dataIndex: 'day', key: 'day' },
  { title: 'Points', dataIndex: 'point', key: 'point' },
  { title: 'Time', dataIndex: 'createTime', key: 'createTime' },
];

async function loadSummary() {
  try {
    summary.value = await requestClient.get('/member/sign-in/record/get-summary');
  } catch {
    // ignore
  }
}

async function loadRecords() {
  loading.value = true;
  try {
    const result = await requestClient.get('/member/sign-in/record/page', {
      params: { pageNo: pageNo.value, pageSize: 10 },
    });
    records.value = result.list || [];
    total.value = result.total || 0;
  } catch {
    message.error('Failed to load sign-in records');
  } finally {
    loading.value = false;
  }
}

async function handleSignIn() {
  try {
    await requestClient.post('/member/sign-in/record/create');
    message.success('Sign-in successful!');
    loadSummary();
    loadRecords();
  } catch {
    message.error('Sign-in failed');
  }
}

onMounted(() => {
  loadSummary();
  loadRecords();
});
</script>

<template>
  <div class="p-4">
    <Card title="Daily Sign-In">
      <Row :gutter="16" class="mb-4">
        <Col :span="8">
          <Statistic title="Total Sign-Ins" :value="summary?.totalDay ?? 0" />
        </Col>
        <Col :span="8">
          <Statistic title="Consecutive Days" :value="summary?.continuousDay ?? 0" />
        </Col>
        <Col :span="8">
          <Button type="primary" size="large" @click="handleSignIn">Sign In Today</Button>
        </Col>
      </Row>
      <Table
        :columns="columns"
        :data-source="records"
        :loading="loading"
        :pagination="{ current: pageNo, pageSize: 10, total }"
        row-key="id"
      />
    </Card>
  </div>
</template>
