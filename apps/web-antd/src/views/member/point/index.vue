<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import { Card, Table, message } from 'ant-design-vue';
import { requestClient } from '#/api/request';

const loading = ref(false);
const dataSource = ref<any[]>([]);
const total = ref(0);
const pageNo = ref(1);
const pageSize = ref(10);

const columns = [
  { title: 'Title', dataIndex: 'title', key: 'title' },
  { title: 'Points', dataIndex: 'point', key: 'point' },
  { title: 'Description', dataIndex: 'description', key: 'description' },
  { title: 'Time', dataIndex: 'createTime', key: 'createTime' },
];

async function loadPage() {
  loading.value = true;
  try {
    const result = await requestClient.get('/member/point/record/page', {
      params: { pageNo: pageNo.value, pageSize: pageSize.value },
    });
    dataSource.value = result.list || [];
    total.value = result.total || 0;
  } catch {
    message.error('Failed to load point records');
  } finally {
    loading.value = false;
  }
}

function handleTableChange(pagination: any) {
  pageNo.value = pagination.current;
  pageSize.value = pagination.pageSize;
  loadPage();
}

onMounted(() => {
  loadPage();
});
</script>

<template>
  <div class="p-4">
    <Card title="My Points">
      <Table
        :columns="columns"
        :data-source="dataSource"
        :loading="loading"
        :pagination="{ current: pageNo, pageSize, total }"
        row-key="id"
        @change="handleTableChange"
      />
    </Card>
  </div>
</template>
