import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    meta: {
      icon: 'lucide:wallet',
      order: 2,
      title: '支付中心',
    },
    name: 'PayCenter',
    path: '/pay',
    children: [
      {
        name: 'PayWallet',
        path: '/pay/wallet',
        component: () => import('#/views/pay/wallet/index.vue'),
        meta: {
          icon: 'lucide:wallet-minimal',
          title: '我的钱包',
        },
      },
      {
        name: 'PayTransaction',
        path: '/pay/transaction',
        component: () => import('#/views/pay/transaction/index.vue'),
        meta: {
          icon: 'lucide:receipt',
          title: '交易记录',
        },
      },
      {
        name: 'PayRecharge',
        path: '/pay/recharge',
        component: () => import('#/views/pay/recharge/index.vue'),
        meta: {
          icon: 'lucide:plus-circle',
          title: '充值',
        },
      },
    ],
  },
];

export default routes;
