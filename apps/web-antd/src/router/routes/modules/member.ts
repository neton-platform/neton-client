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
        name: 'MemberInfo',
        path: '/member/info',
        component: () => import('#/views/member/info/index.vue'),
        meta: {
          icon: 'lucide:user-circle',
          title: '基本信息',
        },
      },
      {
        name: 'MemberAddress',
        path: '/member/address',
        component: () => import('#/views/member/address/index.vue'),
        meta: {
          icon: 'lucide:map-pin',
          title: '收货地址',
        },
      },
      {
        name: 'MemberPoint',
        path: '/member/point',
        component: () => import('#/views/member/point/index.vue'),
        meta: {
          icon: 'lucide:coins',
          title: '我的积分',
        },
      },
      {
        name: 'MemberSignIn',
        path: '/member/sign-in',
        component: () => import('#/views/member/sign-in/index.vue'),
        meta: {
          icon: 'lucide:calendar-check',
          title: '每日签到',
        },
      },
      {
        name: 'MemberSocial',
        path: '/member/social',
        component: () => import('#/views/member/social/index.vue'),
        meta: {
          icon: 'lucide:share-2',
          title: '社交账号',
        },
      },
    ],
  },
];

export default routes;
