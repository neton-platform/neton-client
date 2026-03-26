import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    meta: {
      icon: 'lucide:user',
      order: 1,
      title: '个人中心',
    },
    name: 'MemberCenter',
    path: '/member',
    children: [
      {
        name: 'MemberAccountOverview',
        path: '/member/account/overview',
        component: () => import('#/views/member/account/overview/index.vue'),
        meta: {
          icon: 'lucide:credit-card',
          title: '账户概览',
        },
      },
      {
        name: 'MemberAccountCharges',
        path: '/member/account/charges',
        component: () => import('#/views/member/account/charges/index.vue'),
        meta: {
          icon: 'lucide:wallet',
          title: '消费记录',
        },
      },
      {
        name: 'MemberAccountLogs',
        path: '/member/account/logs',
        component: () => import('#/views/member/account/logs/index.vue'),
        meta: {
          icon: 'lucide:file-text',
          title: '请求日志',
        },
      },
    ],
  },
];

export default routes;
