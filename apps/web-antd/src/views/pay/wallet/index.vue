<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import { Card, Statistic, Row, Col, Button, message } from 'ant-design-vue';
import { useRouter } from 'vue-router';
import { requestClient } from '#/api/request';

const router = useRouter();
const loading = ref(false);
const wallet = ref<any>(null);

async function loadWallet() {
  loading.value = true;
  try {
    wallet.value = await requestClient.get('/pay/wallet/get');
  } catch {
    message.error('Failed to load wallet info');
  } finally {
    loading.value = false;
  }
}

function goRecharge() {
  router.push('/pay/recharge');
}

function goTransaction() {
  router.push('/pay/transaction');
}

onMounted(() => {
  loadWallet();
});
</script>

<template>
  <div class="p-4">
    <Card title="My Wallet" :loading="loading">
      <Row :gutter="24" class="mb-6">
        <Col :span="8">
          <Statistic
            title="Balance"
            :value="(wallet?.balance ?? 0) / 100"
            :precision="2"
            prefix="&yen;"
          />
        </Col>
        <Col :span="8">
          <Statistic
            title="Total Recharge"
            :value="(wallet?.totalRecharge ?? 0) / 100"
            :precision="2"
            prefix="&yen;"
          />
        </Col>
        <Col :span="8">
          <Statistic
            title="Total Expense"
            :value="(wallet?.totalExpense ?? 0) / 100"
            :precision="2"
            prefix="&yen;"
          />
        </Col>
      </Row>
      <div class="flex gap-3">
        <Button type="primary" @click="goRecharge">Recharge</Button>
        <Button @click="goTransaction">Transaction History</Button>
      </div>
    </Card>
  </div>
</template>
