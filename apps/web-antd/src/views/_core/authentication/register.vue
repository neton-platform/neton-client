<script lang="ts" setup>
import type { VbenFormSchema } from '@vben/common-ui';

import { computed, h, ref } from 'vue';

import { AuthenticationRegister, z } from '@vben/common-ui';
import { $t } from '@vben/locales';

import { message } from 'ant-design-vue';

import { sendSmsCode } from '#/api';
import { useAuthStore } from '#/store';

defineOptions({ name: 'Register' });

const loading = ref(false);
const CODE_LENGTH = 4;

const authStore = useAuthStore();
const registerRef = ref();

/** 执行注册：通过短信验证码登录，后端会自动创建用户 */
async function handleRegister(values: any) {
  loading.value = true;
  try {
    await authStore.authLogin('mobile', {
      mobile: values.mobile,
      code: values.code,
    });
  } catch (error) {
    console.error('Error in handleRegister:', error);
  } finally {
    loading.value = false;
  }
}

const formSchema = computed((): VbenFormSchema[] => {
  return [
    {
      component: 'VbenInput',
      componentProps: {
        placeholder: $t('authentication.mobile'),
      },
      fieldName: 'mobile',
      label: $t('authentication.mobile'),
      rules: z
        .string()
        .min(1, { message: $t('authentication.mobileTip') })
        .refine((v) => /^\d{11}$/.test(v), {
          message: $t('authentication.mobileErrortip'),
        }),
    },
    {
      component: 'VbenPinInput',
      componentProps: {
        codeLength: CODE_LENGTH,
        createText: (countdown: number) => {
          const text =
            countdown > 0
              ? $t('authentication.sendText', [countdown])
              : $t('authentication.sendCode');
          return text;
        },
        placeholder: $t('authentication.code'),
        handleSendCode: async () => {
          loading.value = true;
          try {
            const formApi = registerRef.value?.getFormApi();
            if (!formApi) {
              throw new Error('表单未准备好');
            }
            await formApi.validateField('mobile');
            const isMobileValid = await formApi.isFieldValid('mobile');
            if (!isMobileValid) {
              throw new Error('请输入有效的手机号码');
            }

            const { mobile } = await formApi.getValues();
            const scene = 1; // 场景：会员登录（首次登录自动注册）
            await sendSmsCode({ mobile, scene });
            message.success('验证码发送成功');
          } finally {
            loading.value = false;
          }
        },
      },
      fieldName: 'code',
      label: $t('authentication.code'),
      rules: z.string().length(CODE_LENGTH, {
        message: $t('authentication.codeTip', [CODE_LENGTH]),
      }),
    },
    {
      component: 'VbenCheckbox',
      fieldName: 'agreePolicy',
      renderComponentContent: () => ({
        default: () =>
          h('span', [
            $t('authentication.agree'),
            h(
              'a',
              {
                class: 'vben-link ml-1 ',
                href: '',
              },
              `${$t('authentication.privacyPolicy')} & ${$t('authentication.terms')}`,
            ),
          ]),
      }),
      rules: z.boolean().refine((value) => !!value, {
        message: $t('authentication.agreeTip'),
      }),
    },
  ];
});
</script>

<template>
  <AuthenticationRegister
    ref="registerRef"
    :form-schema="formSchema"
    :loading="loading"
    @submit="handleRegister"
  />
</template>
