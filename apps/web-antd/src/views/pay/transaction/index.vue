<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import { Card, Table, Tag, message } from 'ant-design-vue';
import { requestClient } from '#/api/request';

const loading = ref(false);
const dataSource = ref<any[]>([]);
const total = ref(0);
const pageNo = ref(1);
const pageSize = ref(10);

const columns = [
  { title: 'Title', dataIndex: 'title', key: 'title' },
  {
    title: 'Amount',
    dataIndex: 'price',
    key: 'price',
  },
  { title: 'Balance After', dataIndex: 'balance', key: 'balance' },
  { title: 'Time', dataIndex: 'createTime', key: 'createTime' },
];

async function loadPage() {
  loading.value = true;
  try {
    const result = await requestClient.get('/pay/wallet-transaction/page', {
      params: { pageNo: pageNo.value, pageSize: pageSize.value },
    });
    dataSource.value = result.list || [];
    total.value = result.total || 0;
  } catch {
    message.error('Failed to load transactions');
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
    <Card title="Transaction History">
      <Table
        :columns="columns"
        :data-source="dataSource"
        :loading="loading"
        :pagination="{ current: pageNo, pageSize, total }"
        row-key="id"
        @change="handleTableChange"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'price'">
            <Tag :color="record.price > 0 ? 'green' : 'red'">
              {{ record.price > 0 ? '+' : '' }}{{ (record.price / 100).toFixed(2) }}
            </Tag>
          </template>
          <template v-if="column.key === 'balance'">
            &yen;{{ (record.balance / 100).toFixed(2) }}
          </template>
        </template>
      </Table>
    </Card>
  </div>
</template>
