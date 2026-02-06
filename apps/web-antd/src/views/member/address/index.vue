<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import { Card, Table, Button, Tag, message, Popconfirm } from 'ant-design-vue';
import { requestClient } from '#/api/request';

interface Address {
  id: number;
  name: string;
  mobile: string;
  areaName: string;
  detailAddress: string;
  defaultStatus: boolean;
}

const loading = ref(false);
const dataSource = ref<Address[]>([]);

const columns = [
  { title: 'Name', dataIndex: 'name', key: 'name' },
  { title: 'Phone', dataIndex: 'mobile', key: 'mobile' },
  { title: 'Area', dataIndex: 'areaName', key: 'areaName' },
  { title: 'Detail', dataIndex: 'detailAddress', key: 'detailAddress' },
  { title: 'Default', dataIndex: 'defaultStatus', key: 'defaultStatus' },
  { title: 'Actions', key: 'actions' },
];

async function loadList() {
  loading.value = true;
  try {
    dataSource.value = await requestClient.get<Address[]>('/member/address/list');
  } catch {
    message.error('Failed to load address list');
  } finally {
    loading.value = false;
  }
}

async function handleDelete(id: number) {
  try {
    await requestClient.delete(`/member/address/delete?id=${id}`);
    message.success('Deleted');
    loadList();
  } catch {
    message.error('Failed to delete');
  }
}

onMounted(() => {
  loadList();
});
</script>

<template>
  <div class="p-4">
    <Card title="Shipping Address">
      <Table :columns="columns" :data-source="dataSource" :loading="loading" row-key="id">
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'defaultStatus'">
            <Tag :color="record.defaultStatus ? 'green' : 'default'">
              {{ record.defaultStatus ? 'Default' : '-' }}
            </Tag>
          </template>
          <template v-if="column.key === 'actions'">
            <Popconfirm title="Confirm delete?" @confirm="handleDelete(record.id)">
              <Button type="link" danger size="small">Delete</Button>
            </Popconfirm>
          </template>
        </template>
      </Table>
    </Card>
  </div>
</template>
